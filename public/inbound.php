<?php
//bootstrap, nexmo client config, database
$config = require __DIR__ . '/../bootstrap.php';
$nexmo = $config['nexmo']['client'];
$mongo = $config['mongo']['client'];
$db = $config['mongo']['db'];

//is this a message from Nexmo?
$inbound = \Nexmo\Message\InboundMessage::createFromGlobals();
if(!$inbound->isValid()){
    error_log('not an inbound message');
    return;
}

//look for the user by their number and the group's number
$user = $db->selectCollection('users')->findOne([
    'group' => $inbound->getTo(), // the group's number
    'user'  => $inbound->getFrom() //the user's  number
]);

if($user){
    error_log('found user: ' . $user['name']);
} else {
    error_log('no user found');
}

//check if they sent a command (keyword + optional argument)
$command = preg_split('#\s#', $inbound->getBody(), 2);
switch(strtolower(trim($command[0]))){
    //subscription request
    case 'join';
        error_log('got join command');

        //for new users, we require a name
        if(!$user && empty($command[1])){
            $nexmo->message()->send($inbound->createReply('Use JOIN [your name] to join this group.'));
            break;
        }

        //for new users, we need to setup the initial data
        if(!$user){
            $user = [
                'group' => $inbound->getTo(),
                'user' => $inbound->getFrom(),
                'status' => 'active',
                'actions' => []
            ];
        }

        if(isset($command[1])){
            $user['name'] = $command[1];
        }

        //now set the user to active, and log this action
        $user['status'] = 'active';
        $user['actions'][] = [
            'command' => 'join',
            'date' => new \MongoDB\BSON\UTCDatetime(microtime(true))
        ];

        //update (or insert) the user record
        $db->selectCollection('users')->replaceOne([
            'group' => $inbound->getTo(), // the group's number
            'user'  => $inbound->getFrom() //the user's  number
        ], $user, ['upsert' => true]);

        error_log('added user');
        break;

    //unsubscribe request
    case 'leave';
        error_log('got leave command');

        //leave only makes sense if the user exists
        if(!$user){
            $nexmo->message()->send($inbound->createReply('Use JOIN [your name] to join this group.'));
            break;
        }

        //update the user's status
        $user['status'] = 'inactive';
        $user['actions'][] = [
            'command' => 'leave',
            'date' => new \MongoDB\BSON\UTCDatetime(microtime(true))
        ];

        //let them know they've left
        $nexmo->message()->send($inbound->createReply('You have left. Use JOIN to join this group again.'));

        //update the database
        $db->selectCollection('users')->replaceOne([
            'group' => $inbound->getTo(), // the group's number
            'user'  => $inbound->getFrom() //the user's  number
        ], $user);

        error_log('removed user');
        break;

    //no command found
    default:
        error_log('no command found');

        //only active users can post to the group
        if(!$user || 'active' != $user['status']){
            $nexmo->message()->send($inbound->createReply('Use JOIN [your name] to join this group.'));
            break;
        }

        error_log('user is active');

        //create a log of the inbound message
        $log = [
            '_id'   => $inbound->getMessageId(),
            'text'  => $inbound->getBody(),
            'date'  => new \MongoDB\BSON\UTCDatetime(microtime(true)),
            'group' => $inbound->getTo(),
            'user'  => $inbound->getFrom(),
            'name'  => $user['name'],
            'sends' => []
        ];

        //get all the group's users except the server
        $members = $db->selectCollection('users')->find([
            'group'  => $inbound->getTo(),
            'user'   => ['$ne' => $inbound->getFrom()],
            'status' => 'active'
        ]);

        foreach($members as $member) {
            $sent = $nexmo->message()->send([
                'to'   => $member['user'],
                'from' => $inbound->getTo(),
                'text' => $user['name'] . ': ' . $inbound->getBody()
            ]);

            $log['sends'][] = [
                'user' => $sent->getTo(),
                'id'   => $sent->getMessageId()
            ];
        }
        $db->selectCollection('logs')->insertOne($log);

        error_log('relayed message');
        break;
}