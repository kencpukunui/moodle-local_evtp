<?php
/**
 * EVTP Training Plans
 *
 * Training plan edit.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.
$action  = optional_param('action', '', PARAM_ALPHA);
$cancel  = optional_param('cancel', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$itemid  = optional_param('itemid', 0, PARAM_INT);
$planid  = required_param('id', PARAM_INT);

// Redirect if form was cancelled.
if (!empty($cancel)) {
    redirect(new moodle_url('/local/evtp/templateplanlist.php'));
    exit;
}

// Set up basic information.
$url     = new moodle_url('/local/evtp/templateplanedit.php');
$context = context_system::instance();
$title   = get_string('trainingplanedit', 'local_evtp');

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
$PAGE->navbar->add(get_string('trainingplans', 'local_evtp'));
$PAGE->navbar->add(get_string('templateplanlist', 'local_evtp'), new moodle_url('/local/evtp/templateplanlist.php'));
$PAGE->navbar->add($title);

$output = $PAGE->get_renderer('local_evtp');

// Respond to any actions.
switch ($action) {

    // Edit the plan?
    case 'edit':
        if (\local_evtp\utils::training_plan_edit()) {
            $notifications = $output->notify_success(get_string('planupdated', 'local_evtp'));
        } else {
            $notifications = $output->notify_problem(get_string('plannotupdated', 'local_evtp'));
        }
        break;

    // Delete an item?
    case 'del':
        if (empty($confirm)) {
            if ($out = $output->training_plan_item_delete_confirmation($planid, $itemid)) {
                echo $output->header();
                echo $out;
                echo $output->footer();
                exit;
            } else {
                $notifications = $output->notify_problem(get_string('unknownitemid', 'local_evtp'));
            }
        } else {
            if (\local_evtp\utils::training_plan_item_delete($itemid)) {
                $notifications = $output->notify_success(get_string('itemdeleted', 'local_evtp'));
            } else {
                $notifications = $output->notify_problem(get_string('itemnotdeleted', 'local_evtp'));
            }
        }
        break;


    // Move an item up?
    case 'up':
        if (\local_evtp\utils::training_plan_item_move($planid, $itemid, 'up')) {
            $notifications = $output->notify_success(get_string('itemmovedup', 'local_evtp'));
        } else {
            $notifications = $output->notify_problem(get_string('itemnotmovedup', 'local_evtp'));
        }
        break;

    // Move an item down?
    case 'down';
        if (\local_evtp\utils::training_plan_item_move($planid, $itemid, 'down')) {
            $notifications = $output->notify_success(get_string('itemmoveddown', 'local_evtp'));
        } else {
            $notifications = $output->notify_problem(get_string('itemnotmoveddown', 'local_evtp'));
        }
        break;

    default:
        $notifications = '';
}

echo $output->header();
echo $notifications;
echo $output->template_plan_edit($planid);
echo $output->footer();
