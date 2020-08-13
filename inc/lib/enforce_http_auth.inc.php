<?php
function isAuthenticatedUser() {
  if(isset($_SERVER['PHP_AUTH_USER'])) $user = $_SERVER['PHP_AUTH_USER'];
  else if(isset($_SERVER['REMOTE_USER'])) $user = $_SERVER['REMOTE_USER'];
  else if(isset($_SERVER['REDIRECT_REMOTE_USER'])) $user = $_SERVER['REDIRECT_REMOTE_USER'];
  else return false;

  if(strlen($user) > 0) return true;
  else return false;
}
