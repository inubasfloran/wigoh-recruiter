/*
 * Lifecycle Management JavaScript Library
 * Handles stage/substatus picker and pipeline stage filtering.
 */

var _lcSubstatuses = {};
var _stageFilter = 0;

/**
 * Stores sub-statuses data from PHP (grouped by stageID).
 */
function LC_initSubstatuses(grouped)
{
    _lcSubstatuses = grouped;
}

/**
 * Toggle visibility of the lifecycle picker section in the modal.
 */
function LC_onChangeLifecycleToggle()
{
    var checkbox = document.getElementById('changeLifecycleStage');
    var div = document.getElementById('lifecycleDiv');
    var stageSelect = document.getElementById('lifecycleStageSelect');
    var substatusSelect = document.getElementById('lifecycleSubstatusSelect');

    if (checkbox && checkbox.checked)
    {
        if (div) div.style.display = '';
        if (stageSelect) stageSelect.disabled = false;
        if (substatusSelect) substatusSelect.disabled = false;
    }
    else
    {
        if (div) div.style.display = 'none';
        if (stageSelect) stageSelect.disabled = true;
        if (substatusSelect) substatusSelect.disabled = true;
        /* Clear hidden fields */
        var hiddenStage = document.getElementById('lifecycleStageID');
        var hiddenSub = document.getElementById('lifecycleSubstatusID');
        if (hiddenStage) hiddenStage.value = '0';
        if (hiddenSub) hiddenSub.value = '0';
    }
}

/**
 * Populates sub-status dropdown when a stage is selected.
 */
function LC_onStageChange()
{
    var stageSelect = document.getElementById('lifecycleStageSelect');
    var substatusSelect = document.getElementById('lifecycleSubstatusSelect');
    var hiddenStage = document.getElementById('lifecycleStageID');
    var hiddenSub = document.getElementById('lifecycleSubstatusID');

    if (!stageSelect || !substatusSelect) return;

    var stageID = parseInt(stageSelect.value, 10);
    if (hiddenStage) hiddenStage.value = stageID;

    /* Clear existing options */
    substatusSelect.innerHTML = '';

    if (stageID <= 0 || !_lcSubstatuses[stageID])
    {
        var opt = document.createElement('option');
        opt.value = '0';
        opt.text = '(Select a Stage first)';
        substatusSelect.appendChild(opt);
        if (hiddenSub) hiddenSub.value = '0';
        return;
    }

    var subs = _lcSubstatuses[stageID];
    for (var i = 0; i < subs.length; i++)
    {
        var opt = document.createElement('option');
        opt.value = subs[i].substatusID;
        opt.text = subs[i].substatusName;
        if (subs[i].isDefault == '1')
        {
            opt.selected = true;
            if (hiddenSub) hiddenSub.value = subs[i].substatusID;
        }
        substatusSelect.appendChild(opt);
    }

    /* If no default was set, select the first one */
    if (hiddenSub && (hiddenSub.value == '0' || hiddenSub.value == '') && subs.length > 0)
    {
        hiddenSub.value = subs[0].substatusID;
        substatusSelect.selectedIndex = 0;
    }
}

/**
 * Updates hidden substatus field when substatus dropdown changes.
 */
function LC_onSubstatusChange()
{
    var substatusSelect = document.getElementById('lifecycleSubstatusSelect');
    var hiddenSub = document.getElementById('lifecycleSubstatusID');
    if (substatusSelect && hiddenSub)
    {
        hiddenSub.value = substatusSelect.value;
    }
}

/**
 * Filters the pipeline table by a specific stage.
 * Triggers AJAX reload with stageFilter parameter.
 */
function PipelineJobOrder_filterByStage(stageID, jobOrderID, page, entriesPerPage,
    sortBy, sortDirection, isPopup, htmlObjectID, sessionCookie, indicatorID, indexFile)
{
    _stageFilter = stageID;
    PipelineJobOrder_populate(jobOrderID, page, entriesPerPage, sortBy,
        sortDirection, isPopup, htmlObjectID, sessionCookie, indicatorID, indexFile);
}
