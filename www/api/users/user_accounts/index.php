<?php
/**
 * User account script for the RMS API.
 *
 * Allows read and write access to user accounts via the RMS API.
 *
 * @author     Russell Toris <rctoris@wpi.edu>
 * @copyright  2012 Russell Toris, Worcester Polytechnic Institute
 * @license    BSD -- see LICENSE file
 * @version    December, 4 2012
 * @package    api.users.user_accounts
 * @link       http://ros.org/wiki/rms
 */

include_once(dirname(__FILE__).'/../../../inc/config.inc.php');
include_once(dirname(__FILE__).'/../../api.inc.php');
include_once(dirname(__FILE__).'/../../config/logs/logs.inc.php');
include_once(dirname(__FILE__).'/user_accounts.inc.php');

// JSON response
header('Content-type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// check for authorization
if($auth = authenticate()) {
  // default to the 404 state
  $result = create_404_state();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
      // check if this is a request
      if(isset($_POST['request'])) {
        switch ($_POST['request']) {
          case 'server_session':
            if(count($_POST) === 1) {
              // create the session
              session_start();
              $_SESSION['userid'] = $auth['userid'];
              $result = create_200_state($auth);
              write_to_log('SESSION: '.$auth['username'].' created a new session.');
            }
            break;
          case 'destroy_session':
            if(count($_POST) === 1) {
              // destroy the session
              unset($_SESSION['userid']);
              session_destroy();
              $result = create_200_state(null);
              write_to_log('SESSION: '.$auth['username'].' destroyed their session.');
            }
            break;
          default:
            $result['msg'] = $_POST['request'].' request type is invalid.';
            break;
        }
      } else if(valid_user_account_fields($_POST)) {
        if($auth['type'] === 'admin') {
          $error = create_user_account($_POST['username'], $_POST['password'], $_POST['firstname']
          , $_POST['lastname'], $_POST['email'], $_POST['type']);
          if($error) {
            $result['msg'] = $error;
          } else {
            write_to_log('EDIT: '.$auth['username'].' created user '.$_POST['username'].'.');
            $result = create_200_state(null);
          }
        } else {
          write_to_log('SECURITY: '.$auth['username'].' attempted to create a user.');
          $result = create_401_state();
        }
      }
      break;
    case 'GET':
      if(count($_GET) === 0) {
        // check the user level
        if($auth['type'] === 'admin') {
          // we authenticated so we know at least one user exists
          $result = create_200_state(get_user_accounts());
        } else {
          write_to_log('SECURITY: '.$auth['username'].' attempted to get all users.');
          $result = create_401_state();
        }
      } else if(count($_GET) === 1 && isset($_GET['id'])) {
        // check the user level
        if($auth['type'] === 'admin' || $auth['userid'] === $_GET['id']) {
          $user = get_user_account_by_id($_GET['id']);
          // check if it exists
          if($user) {
            $result = create_200_state($user);
          } else {
            $result['msg'] = 'User with ID '.$_GET['id'].' does not exist.';
          }
        } else {
          write_to_log('SECURITY: '.$auth['username'].' attempted to get user ID '.$_GET['id'].'.');
          $result = create_401_state();
        }
      } else if(isset($_GET['request'])) {
        switch ($_GET['request']) {
          case 'editor':
            if($auth['type'] !== 'admin') {
              write_to_log('SECURITY: '.$auth['username'].' attempted to get a user editor.');
              $result = create_401_state($result);
            } else {
              if(count($_GET) === 1) {
                $html = array();
                $html['html'] = get_user_account_editor_html(null);
                $result = create_200_state($result, $html);
              } else if(count($_GET) === 2 && isset($_GET['id'])) {
                $html = array();
                $html['html'] = get_user_account_editor_html($_GET['id']);
                $result = create_200_state($result, $html);
              }
            }
            break;
          default:
            $result['msg'] = $_GET['request'].' request type is invalid.';
            break;
        }
      }
      break;
    case 'DELETE':
      if(count($_DELETE) === 1 && isset($_DELETE['id'])) {
        if($auth['type'] !== 'admin') {
          write_to_log('SECURITY: '.$auth['username'].' attempted to delete user ID '.$_DELETE['id'].'.');
          $result = create_401_state($result);
        } else {
          $error = delete_user_account_by_id($_DELETE['id']);
          if($error) {
            $result['msg'] = $error;
          } else {
            write_to_log('EDIT: '.$auth['username'].' deleted user ID '.$_DELETE['id'].'.');
            $result = create_200_state($result, null);
          }
        }
      }
      break;
    case 'PUT':
      if(isset($_PUT['id'])) {
        if($auth['type'] !== 'admin') {
          write_to_log('SECURITY: '.$auth['username'].' attempted to edit user ID '.$_PUT['id'].'.');
          $result = create_401_state($result);
        } else {
          $error = update_user_account($_PUT);
          if($error) {
            $result['msg'] = $error;
          } else {
            write_to_log('EDIT: '.$auth['username'].' modified user ID '.$_PUT['id'].'.');
            $result = create_200_state($result, null);
          }
        }
      }
      break;
    default:
      write_to_log('SECURITY: '.$auth['username'].' attempted to make a user acccount '.$_SERVER['REQUEST_METHOD'].' request.');
      $result['msg'] = $_SERVER['REQUEST_METHOD'].' method is unavailable.';
      break;
  }
} else {
  $result = create_401_state();
}



// return the JSON encoding of the result
echo json_encode($result);
?>
