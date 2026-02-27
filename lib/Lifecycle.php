<?php
/**
 * CATS
 * Candidate Lifecycle Management Library
 *
 * @package    CATS
 * @subpackage Library
 */

class Lifecycle
{
    private $_db;
    private $_siteID;

    public function __construct($siteID)
    {
        $this->_siteID = $siteID;
        $this->_db = DatabaseConnection::getInstance();
    }

    /**
     * Returns all enabled lifecycle stages.
     *
     * @return array stages result set
     */
    public function getStages()
    {
        $sql = "SELECT
                stage_id AS stageID,
                stage_name AS stageName,
                color_hex AS colorHex,
                maps_to_pipeline_status AS mapsToPipelineStatus,
                sort_order AS sortOrder
            FROM
                candidate_lifecycle_stage
            WHERE
                is_enabled = 1
            ORDER BY
                sort_order ASC";

        return $this->_db->getAllAssoc($sql);
    }

    /**
     * Returns sub-statuses for a given stage.
     *
     * @param integer stage ID
     * @return array sub-statuses result set
     */
    public function getSubstatuses($stageID)
    {
        $sql = sprintf(
            "SELECT
                substatus_id AS substatusID,
                stage_id AS stageID,
                substatus_name AS substatusName,
                is_default AS isDefault,
                is_terminal AS isTerminal,
                sort_order AS sortOrder
            FROM
                candidate_lifecycle_substatus
            WHERE
                stage_id = %s
            ORDER BY
                sort_order ASC",
            $this->_db->makeQueryInteger($stageID)
        );

        return $this->_db->getAllAssoc($sql);
    }

    /**
     * Returns all sub-statuses grouped by stage ID (for modal JS init).
     *
     * @return array keyed by stageID
     */
    public function getAllSubstatusesGrouped()
    {
        $sql = "SELECT
                substatus_id AS substatusID,
                stage_id AS stageID,
                substatus_name AS substatusName,
                is_default AS isDefault,
                is_terminal AS isTerminal,
                sort_order AS sortOrder
            FROM
                candidate_lifecycle_substatus
            ORDER BY
                stage_id ASC, sort_order ASC";

        $rs = $this->_db->getAllAssoc($sql);
        $grouped = array();

        foreach ($rs as $row)
        {
            $stageID = intval($row['stageID']);
            if (!isset($grouped[$stageID]))
            {
                $grouped[$stageID] = array();
            }
            $grouped[$stageID][] = $row;
        }

        return $grouped;
    }

    /**
     * Returns stages with candidate counts for a job order (for tracker bar).
     *
     * @param integer job order ID
     * @return array stages with counts
     */
    public function getStagesWithCounts($jobOrderID)
    {
        $stages = $this->getStages();

        $sql = sprintf(
            "SELECT
                lifecycle_stage_id AS stageID,
                COUNT(*) AS cnt
            FROM
                candidate_joborder
            WHERE
                joborder_id = %s
            AND
                site_id = %s
            AND
                lifecycle_stage_id IS NOT NULL
            GROUP BY
                lifecycle_stage_id",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        $countsRS = $this->_db->getAllAssoc($sql);
        $countsMap = array();
        foreach ($countsRS as $row)
        {
            $countsMap[intval($row['stageID'])] = intval($row['cnt']);
        }

        foreach ($stages as $index => $stage)
        {
            $sid = intval($stage['stageID']);
            $stages[$index]['count'] = isset($countsMap[$sid]) ? $countsMap[$sid] : 0;
        }

        return $stages;
    }

    /**
     * Sets lifecycle stage and substatus for a candidate-joborder pipeline entry.
     *
     * @param integer candidate ID
     * @param integer job order ID
     * @param integer lifecycle stage ID
     * @param integer lifecycle substatus ID
     * @param integer changed by user ID
     * @param string  reject reason (optional)
     * @param string  change source (manual|gateway|system)
     */
    public function setStageStatus($candidateID, $jobOrderID, $stageID, $substatusID, $changedBy, $rejectReason = '', $changeSource = 'manual')
    {
        /* Get current stage/substatus */
        $sql = sprintf(
            "SELECT
                lifecycle_stage_id AS currentStageID,
                lifecycle_substatus_id AS currentSubstatusID
            FROM
                candidate_joborder
            WHERE
                candidate_id = %s
            AND
                joborder_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryInteger($candidateID),
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        $current = $this->_db->getAssoc($sql);
        if (empty($current))
        {
            return;
        }

        $currentStageID = isset($current['currentStageID']) ? intval($current['currentStageID']) : 0;
        $currentSubstatusID = isset($current['currentSubstatusID']) ? intval($current['currentSubstatusID']) : 0;

        /* Skip if unchanged */
        if ($currentStageID == $stageID && $currentSubstatusID == $substatusID)
        {
            return;
        }

        /* Update lifecycle columns */
        $sql = sprintf(
            "UPDATE
                candidate_joborder
            SET
                lifecycle_stage_id = %s,
                lifecycle_substatus_id = %s,
                reject_reason = %s,
                date_modified = NOW()
            WHERE
                candidate_id = %s
            AND
                joborder_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryInteger($stageID),
            $this->_db->makeQueryInteger($substatusID),
            (!empty($rejectReason) ? "'" . addslashes($rejectReason) . "'" : 'NULL'),
            $this->_db->makeQueryInteger($candidateID),
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        $this->_db->query($sql);

        /* Look up maps_to_pipeline_status and sync legacy status */
        $sql = sprintf(
            "SELECT maps_to_pipeline_status AS legacyStatus
            FROM candidate_lifecycle_stage
            WHERE stage_id = %s",
            $this->_db->makeQueryInteger($stageID)
        );

        $stageData = $this->_db->getAssoc($sql);
        if (!empty($stageData) && isset($stageData['legacyStatus']))
        {
            $legacyStatus = intval($stageData['legacyStatus']);
            $pipelines = new Pipelines($this->_siteID);
            $pipelines->setStatus($candidateID, $jobOrderID, $legacyStatus, '', '');
        }

        /* Record in lifecycle history */
        $sql = sprintf(
            "INSERT INTO candidate_lifecycle_history
                (site_id, candidate_id, joborder_id, stage_from, stage_to,
                 substatus_from, substatus_to, changed_by, change_source,
                 reject_reason, date_created)
            VALUES
                (%s, %s, %s, %s, %s, %s, %s, %s, '%s', %s, NOW())",
            $this->_siteID,
            $this->_db->makeQueryInteger($candidateID),
            $this->_db->makeQueryInteger($jobOrderID),
            ($currentStageID > 0 ? $currentStageID : 'NULL'),
            $this->_db->makeQueryInteger($stageID),
            ($currentSubstatusID > 0 ? $currentSubstatusID : 'NULL'),
            $this->_db->makeQueryInteger($substatusID),
            $this->_db->makeQueryInteger($changedBy),
            addslashes($changeSource),
            (!empty($rejectReason) ? "'" . addslashes($rejectReason) . "'" : 'NULL')
        );

        $this->_db->query($sql);
    }
}
