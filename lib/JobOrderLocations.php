<?php
/**
 * CATS
 * Job Order Locations Library
 *
 * Manages multiple locations for job orders.
 *
 * @package    CATS
 * @subpackage Library
 */

class JobOrderLocations
{
    private $_db;
    private $_siteID;

    public function __construct($siteID)
    {
        $this->_siteID = $siteID;
        $this->_db = DatabaseConnection::getInstance();
    }

    /**
     * Returns all locations for a job order.
     *
     * @param integer $jobOrderID
     * @return array locations data
     */
    public function getByJobOrderID($jobOrderID)
    {
        $sql = sprintf(
            "SELECT
                joborder_location_id AS locationID,
                joborder_id AS jobOrderID,
                site_id AS siteID,
                city,
                state,
                is_primary AS isPrimary,
                date_created AS dateCreated
            FROM
                joborder_location
            WHERE
                joborder_id = %s
            AND
                site_id = %s
            ORDER BY
                is_primary DESC,
                joborder_location_id ASC",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        return $this->_db->getAllAssoc($sql);
    }

    /**
     * Returns the primary location for a job order.
     *
     * @param integer $jobOrderID
     * @return array|null primary location data or null if none
     */
    public function getPrimaryLocation($jobOrderID)
    {
        $sql = sprintf(
            "SELECT
                joborder_location_id AS locationID,
                joborder_id AS jobOrderID,
                site_id AS siteID,
                city,
                state,
                is_primary AS isPrimary,
                date_created AS dateCreated
            FROM
                joborder_location
            WHERE
                joborder_id = %s
            AND
                site_id = %s
            AND
                is_primary = 1
            LIMIT 1",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        $result = $this->_db->getAssoc($sql);
        return !empty($result) ? $result : null;
    }

    /**
     * Adds a location to a job order.
     *
     * @param integer $jobOrderID
     * @param string $city
     * @param string $state
     * @param boolean $isPrimary
     * @return integer new location ID, or -1 on failure
     */
    public function add($jobOrderID, $city, $state, $isPrimary = false)
    {
        // If this is marked as primary, unset any existing primary locations
        if ($isPrimary)
        {
            $this->unsetPrimaryLocations($jobOrderID);
        }

        $sql = sprintf(
            "INSERT INTO joborder_location (
                joborder_id,
                site_id,
                city,
                state,
                is_primary,
                date_created
            ) VALUES (
                %s,
                %s,
                %s,
                %s,
                %s,
                NOW()
            )",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID,
            $this->_db->makeQueryString($city),
            $this->_db->makeQueryString($state),
            ($isPrimary ? '1' : '0')
        );

        $queryResult = $this->_db->query($sql);
        if (!$queryResult)
        {
            return -1;
        }

        $locationID = $this->_db->getLastInsertID();

        // Sync primary location to joborder table for backwards compatibility
        if ($isPrimary)
        {
            $this->syncPrimaryToJobOrder($jobOrderID, $city, $state);
        }

        return $locationID;
    }

    /**
     * Updates a location.
     *
     * @param integer $locationID
     * @param string $city
     * @param string $state
     * @param boolean $isPrimary
     * @return boolean success
     */
    public function update($locationID, $city, $state, $isPrimary = null)
    {
        // Get the job order ID for this location
        $location = $this->getByID($locationID);
        if (empty($location))
        {
            return false;
        }

        $jobOrderID = $location['jobOrderID'];

        // If this is being set as primary, unset others first
        if ($isPrimary === true)
        {
            $this->unsetPrimaryLocations($jobOrderID);
        }

        $sql = sprintf(
            "UPDATE
                joborder_location
            SET
                city = %s,
                state = %s" .
                ($isPrimary !== null ? ", is_primary = " . ($isPrimary ? '1' : '0') : '') .
            " WHERE
                joborder_location_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryString($city),
            $this->_db->makeQueryString($state),
            $this->_db->makeQueryInteger($locationID),
            $this->_siteID
        );

        $queryResult = $this->_db->query($sql);

        // Sync primary location to joborder table for backwards compatibility
        if ($isPrimary === true || ($isPrimary === null && $location['isPrimary']))
        {
            $this->syncPrimaryToJobOrder($jobOrderID, $city, $state);
        }

        return (boolean) $queryResult;
    }

    /**
     * Deletes a location.
     *
     * @param integer $locationID
     * @return boolean success
     */
    public function delete($locationID)
    {
        // Get location info before deleting
        $location = $this->getByID($locationID);
        if (empty($location))
        {
            return false;
        }

        $wasPrimary = $location['isPrimary'];
        $jobOrderID = $location['jobOrderID'];

        $sql = sprintf(
            "DELETE FROM
                joborder_location
            WHERE
                joborder_location_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryInteger($locationID),
            $this->_siteID
        );

        $queryResult = $this->_db->query($sql);

        // If we deleted the primary, promote the next location
        if ($wasPrimary && $queryResult)
        {
            $this->promoteFirstToPrimary($jobOrderID);
        }

        return (boolean) $queryResult;
    }

    /**
     * Deletes all locations for a job order.
     *
     * @param integer $jobOrderID
     * @return boolean success
     */
    public function deleteByJobOrderID($jobOrderID)
    {
        $sql = sprintf(
            "DELETE FROM
                joborder_location
            WHERE
                joborder_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        return (boolean) $this->_db->query($sql);
    }

    /**
     * Sets locations for a job order from a JSON string.
     * This replaces all existing locations.
     *
     * @param integer $jobOrderID
     * @param string $locationsJSON JSON array of locations [{city, state}, ...]
     * @return boolean success
     */
    public function setFromJSON($jobOrderID, $locationsJSON)
    {
        $locations = json_decode($locationsJSON, true);
        if (!is_array($locations) || empty($locations))
        {
            return false;
        }

        return $this->setLocations($jobOrderID, $locations);
    }

    /**
     * Sets locations for a job order from an array.
     * This replaces all existing locations.
     *
     * @param integer $jobOrderID
     * @param array $locations Array of locations [{city, state}, ...]
     * @return boolean success
     */
    public function setLocations($jobOrderID, $locations)
    {
        if (!is_array($locations) || empty($locations))
        {
            return false;
        }

        // Delete existing locations
        $this->deleteByJobOrderID($jobOrderID);

        // Add new locations (first one is primary)
        $isFirst = true;
        foreach ($locations as $location)
        {
            if (!isset($location['city']) && !isset($location['state']))
            {
                continue;
            }

            $city = isset($location['city']) ? trim($location['city']) : '';
            $state = isset($location['state']) ? trim($location['state']) : '';

            // Skip empty locations
            if (empty($city) && empty($state))
            {
                continue;
            }

            $this->add($jobOrderID, $city, $state, $isFirst);
            $isFirst = false;
        }

        return true;
    }

    /**
     * Returns a location by its ID.
     *
     * @param integer $locationID
     * @return array|null location data or null if not found
     */
    public function getByID($locationID)
    {
        $sql = sprintf(
            "SELECT
                joborder_location_id AS locationID,
                joborder_id AS jobOrderID,
                site_id AS siteID,
                city,
                state,
                is_primary AS isPrimary,
                date_created AS dateCreated
            FROM
                joborder_location
            WHERE
                joborder_location_id = %s
            AND
                site_id = %s
            LIMIT 1",
            $this->_db->makeQueryInteger($locationID),
            $this->_siteID
        );

        $result = $this->_db->getAssoc($sql);
        return !empty($result) ? $result : null;
    }

    /**
     * Returns locations formatted as a display string.
     *
     * @param integer $jobOrderID
     * @return string formatted locations string
     */
    public function getDisplayString($jobOrderID)
    {
        $locations = $this->getByJobOrderID($jobOrderID);
        if (empty($locations))
        {
            return '';
        }

        $parts = array();
        foreach ($locations as $location)
        {
            $locationStr = '';
            if (!empty($location['city']) && !empty($location['state']))
            {
                $locationStr = $location['city'] . ', ' . $location['state'];
            }
            else if (!empty($location['city']))
            {
                $locationStr = $location['city'];
            }
            else if (!empty($location['state']))
            {
                $locationStr = $location['state'];
            }

            if (!empty($locationStr))
            {
                if ($location['isPrimary'])
                {
                    $locationStr .= ' (Primary)';
                }
                $parts[] = $locationStr;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Returns locations as JSON array.
     *
     * @param integer $jobOrderID
     * @return string JSON array of locations
     */
    public function getAsJSON($jobOrderID)
    {
        $locations = $this->getByJobOrderID($jobOrderID);
        $result = array();

        foreach ($locations as $location)
        {
            $result[] = array(
                'city' => $location['city'],
                'state' => $location['state']
            );
        }

        return json_encode($result);
    }

    /**
     * Unsets all primary locations for a job order.
     *
     * @param integer $jobOrderID
     * @return boolean success
     */
    private function unsetPrimaryLocations($jobOrderID)
    {
        $sql = sprintf(
            "UPDATE
                joborder_location
            SET
                is_primary = 0
            WHERE
                joborder_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        return (boolean) $this->_db->query($sql);
    }

    /**
     * Promotes the first location to primary if none exists.
     *
     * @param integer $jobOrderID
     * @return boolean success
     */
    private function promoteFirstToPrimary($jobOrderID)
    {
        // Check if there's already a primary
        $primary = $this->getPrimaryLocation($jobOrderID);
        if ($primary !== null)
        {
            return true;
        }

        // Get the first location
        $locations = $this->getByJobOrderID($jobOrderID);
        if (empty($locations))
        {
            // No locations left, clear joborder city/state
            $this->syncPrimaryToJobOrder($jobOrderID, '', '');
            return true;
        }

        // Promote the first one
        $firstLocation = $locations[0];
        $sql = sprintf(
            "UPDATE
                joborder_location
            SET
                is_primary = 1
            WHERE
                joborder_location_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryInteger($firstLocation['locationID']),
            $this->_siteID
        );

        $result = $this->_db->query($sql);

        // Sync to joborder table
        $this->syncPrimaryToJobOrder($jobOrderID, $firstLocation['city'], $firstLocation['state']);

        return (boolean) $result;
    }

    /**
     * Syncs the primary location to the joborder table for backwards compatibility.
     *
     * @param integer $jobOrderID
     * @param string $city
     * @param string $state
     * @return boolean success
     */
    private function syncPrimaryToJobOrder($jobOrderID, $city, $state)
    {
        $sql = sprintf(
            "UPDATE
                joborder
            SET
                city = %s,
                state = %s
            WHERE
                joborder_id = %s
            AND
                site_id = %s",
            $this->_db->makeQueryString($city),
            $this->_db->makeQueryString($state),
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        return (boolean) $this->_db->query($sql);
    }

    /**
     * Counts locations for a job order.
     *
     * @param integer $jobOrderID
     * @return integer count
     */
    public function getCount($jobOrderID)
    {
        $sql = sprintf(
            "SELECT COUNT(*) AS count
            FROM joborder_location
            WHERE joborder_id = %s AND site_id = %s",
            $this->_db->makeQueryInteger($jobOrderID),
            $this->_siteID
        );

        return (int) $this->_db->getColumn($sql, 0, 0);
    }
}

?>
