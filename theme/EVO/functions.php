<?php
## Code php du thème...
// Bridge theme auth globals to policy engine
$evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
$ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());
\Template\Template::addGlobal('IS_LOGGED', $evaluator->canAccess(new \Core\Navigation\GenericResource('system:access'), $ctx));
\Template\Template::addGlobal('isLoggedIn', $evaluator->canAccess(new \Core\Navigation\GenericResource('system:access'), $ctx));
?>