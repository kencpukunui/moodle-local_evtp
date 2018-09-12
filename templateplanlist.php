<?php
/**
 * EVTP Training Plans
 *
 * Training plan list.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.
$action  = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$planid  = optional_param('planid', 0, PARAM_INT);

// Set up basic information.
$url     = new moodle_url('/local/evtp/templateplanlist.php');
$context = context_system::instance();
$title   = get_string('templateplanlist', 'local_evtp');

// Sanity checks.
require_login();
require_capability('local/evtp:manage', $context);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);

$output = $PAGE->get_renderer('local_evtp');

// Respond to any actions.
switch ($action) {

    // Change active status?
    case 'active':
        \local_evtp\utils::training_plan_change_status($planid);
        redirect($url);
        break;

    // Add a new plan?
    case 'add':
        if (\local_evtp\utils::training_plan_add()) {
            $notifications = $output->notify_success(get_string('planadded', 'local_evtp'));
        } else {
            $notifications = $output->notify_problem(get_string('plannotadded', 'local_evtp'));
        }
        redirect($url, $notifications);
        exit;
        break;

    // Delete a plan?
    case 'del':
        if (empty($confirm)) {
            if ($out = $output->training_plan_delete_confirmation($planid)) {
                echo $output->header();
                echo $out;
                echo $output->footer();
                exit;
            } else {
                $notifications = $output->notify_problem(get_string('unknownplanid', 'local_evtp'));
            }
        } else {
            if (\local_evtp\utils::training_plan_delete($planid)) {
                $notifications = $output->notify_success(get_string('plandeleted', 'local_evtp'));
            } else {
                $notifications = $output->notify_problem(get_string('plannotdeleted', 'local_evtp'));
            }
        }
        break;
        
    // Duplicate a plan?
    case 'dup':
            \local_evtp\utils::training_plan_duplicate($planid);
            redirect($url);
            break;
        break;

    default:
        $notifications = '';
}

echo $output->header();
echo $notifications;
echo $output->template_plan_list();
echo $output->footer();
