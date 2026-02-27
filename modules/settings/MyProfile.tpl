<?php /* $Id: MyProfile.tpl 2452 2007-05-11 17:47:55Z brian $ */ ?>
<?php TemplateUtility::printHeader('Settings', array('modules/settings/validator.js', 'js/sorttable.js')); ?>
<?php TemplateUtility::printHeaderBlock(); ?>
<?php TemplateUtility::printTabs($this->active, $this->subActive); ?>
    <div id="main">
        <?php TemplateUtility::printQuickSearch(); ?>

        <div id="contents">
            <table>
                <tr>
                    <td width="3%">
                        <img src="images/settings.gif" width="24" height="24" border="0" alt="Settings" style="margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>Settings: My Profile</h2></td>
                </tr>
            </table>

            <p class="note">Profile</p>

            <?php if ($this->isDemoUser): ?>
                Note that as a demo user, you do not have privileges to modify any settings.
                <br /><br />
            <?php endif; ?>

            <table width="100%">
                <tr>
                    <td width="100%">
                        <table class="searchTable" width="100%">
                            <tr>
                                <td width="230">
                                    <a href="<?php echo(CATSUtility::getIndexName()); ?>?m=settings&amp;a=showUser&amp;userID=<?php echo($this->userID); ?>&amp;privledged=false">
                                        <img src="images/bullet_black.gif" alt="" border="0" />View Profile
                                    </a>
                                </td>
                                <td>
                                    View your current profile to verify your information is correct.
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="<?php echo(CATSUtility::getIndexName()); ?>?m=settings&amp;a=myProfile&amp;s=changePassword">
                                        <img src="images/bullet_black.gif" alt="" border="0" />Change Password
                                    </a>
                                </td>
                                <td>
                                    Change your CATS login password.
                                </td>
                            </tr>
                            <!--<tr>
                                <td>
                                    <a href="<?php echo(CATSUtility::getIndexName()); ?>?m=settings&amp;a=myProfile&amp;s=notificationOptions">
                                        <img src="images/bullet_black.gif" alt="" border="0" />Change Notification Options
                                    </a>
                                </td>
                                <td>
                                    Change how CATS notifies you of new events.
                                </td>
                            </tr>-->
                        </table>
                    </td>
                </tr>
            </table>

            <p class="note">Outlook Calendar</p>

            <table width="100%">
                <tr>
                    <td width="100%">
                        <table class="searchTable" width="100%">
                            <tr>
                                <td width="230" id="outlookConnectionCell">
                                    <span id="outlookStatus">Checking connection...</span>
                                </td>
                                <td id="outlookDescription">
                                    Connect your Outlook calendar to automatically create calendar events when scheduling interviews.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
<script type="text/javascript">
(function() {
    var statusEl = document.getElementById('outlookStatus');
    var descEl = document.getElementById('outlookDescription');

    function checkOutlookStatus() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/v1/outlook/status', true);
        xhr.withCredentials = true;
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.connected) {
                        statusEl.innerHTML = '<img src="images/bullet_black.gif" alt="" border="0" />' +
                            '<span style="color: green; font-weight: bold;">Connected</span> (' + data.email + ')' +
                            '&nbsp;&nbsp;<a href="#" onclick="disconnectOutlook(); return false;" style="color: #cc0000; font-size: 11px;">Disconnect</a>';
                        descEl.innerHTML = 'Your Outlook calendar is connected. Interview events will automatically appear on your calendar.';
                    } else {
                        statusEl.innerHTML = '<a href="/api/v1/outlook/connect"><img src="images/bullet_black.gif" alt="" border="0" />Connect Outlook Calendar</a>';
                        descEl.innerHTML = 'Connect your Outlook calendar to automatically create calendar events when scheduling interviews.';
                    }
                } catch(e) {
                    statusEl.innerHTML = '<a href="/api/v1/outlook/connect"><img src="images/bullet_black.gif" alt="" border="0" />Connect Outlook Calendar</a>';
                }
            } else {
                statusEl.innerHTML = '<a href="/api/v1/outlook/connect"><img src="images/bullet_black.gif" alt="" border="0" />Connect Outlook Calendar</a>';
            }
        };
        xhr.send();
    }

    window.disconnectOutlook = function() {
        if (!confirm('Are you sure you want to disconnect your Outlook calendar?')) return;
        var xhr = new XMLHttpRequest();
        xhr.open('DELETE', '/api/v1/outlook/disconnect', true);
        xhr.withCredentials = true;
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                checkOutlookStatus();
            }
        };
        xhr.send();
    };

    checkOutlookStatus();
})();
</script>
<?php TemplateUtility::printFooter(); ?>
