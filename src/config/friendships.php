<?php

return [
    /**
     * Load migrations from package migrations,
     * If you published the migration files, please set to `false`.
     */
    'migrations' => false,

    /*
     * Models Related.
     */
    'model_namespace' => (int) app()->version() <= 7 ? 'App' : 'App\Models',
    'models' => [
        /*
         * Model name of User model
         */
        'user' => 'User',
        /*
         * Model name of Interaction Relation model
         */
        'friendship' => \Kimdevylder\Friendships\Models\Friendship::class,
    ],

    'tables' => [
        /*
         * Table name of friendships relations.
         */
        'friendships' => 'friendships',
    ],

];
