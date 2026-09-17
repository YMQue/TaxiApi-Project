<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging;

class FirebaseService
{
    protected Database $database;
    protected Messaging $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            //Path to service account file
            ->withServiceAccount(storage_path('app/firebase/laravel-4e461-firebase-adminsdk-fbsvc-b7782b07b2.json'))
            //Change This to firebase realtime database path
            ->withDatabaseUri('https://laravel-4e461-default-rtdb.firebaseio.com');

        $this->database = $factory->createDatabase();
        $this->messaging = $factory->createMessaging();
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getMessaging(): Messaging
    {
        return $this->messaging;
    }
}
