<?php
namespace Kimdevylder\Friendships\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Kimdevylder\Friendships\Interaction;
// use Kimdevylder\Friendships\Models\FriendFriendshipGroups;
use Kimdevylder\Friendships\Models\Friendship;
use Kimdevylder\Friendships\Status;

use DB;
use Log;

/**
 * Class Friendable
 * @package Kimdevylder\Friendships\Traits
 */
trait Friendable
{

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {
        // Log::info('GET FRIENDS');
        // Log::info('-----------------------------------------------------');

        /* $with=['recipient', 'sender'];
        $type= 'all';
        $searchQuery = 'brown';
        
        $friendshipModelName = Interaction::getFriendshipModelName();
        $query = $friendshipModelName::select('friendships.*', 'users.name as name');

        $query->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', DB::raw("(
    			CASE 
    			WHEN users.id != " . $this->getKey() . " && users.id = friendships.sender_id
    			THEN friendships.sender_id
    			WHEN users.id != " . $this->getKey() . " && users.id = friendships.recipient_id
                THEN friendships.recipient_id
    			END)"));

            $join->where('status', 'accepted');
            
        });

        switch ($type) {
            case 'all':
                $query->where(function ($q) {$q->whereRecipient($this);});
                $query->where('users.name', 'LIKE', '%Brown%');
    
                $query->orWhere(function ($q) {$q->whereSender($this);});
                $query->where('users.name', 'LIKE', '%Brown%'); 
                break;
            case 'pending':
                $query->where(function ($q) {$q->whereSender($this);});
                $query->where('users.name', 'LIKE', '%Brown%');  
                break;
            case 'request':
                $query->where(function ($q) {$q->whereRecipient($this);});
               $query->where('users.name', 'LIKE', '%Brown%');  
                break;
        }
    

            return $query
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->through(function ($friendships) {
                return [
                    'friendships' => $friendships,
                ];
            }); */

        return $this->getOrPaginate($this->findFriendships(Status::ACCEPTED, 'all', ['recipient', 'sender'], $filterField, $filterQuery), $perPage, $cursor, ['recipient', 'sender'], $fields);
      
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getPendingFriendships(int $perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::PENDING, 'pending', ['recipient'], $filterField, $filterQuery)->where('sender_id', $this->id), $perPage, $cursor, ['recipient'], $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getFriendshipRequests(int $perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::PENDING, 'request', ['sender'], $filterField, $filterQuery)->where('recipient_id', $this->id), $perPage, $cursor, ['sender'], $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getBlockedFriendships(int $perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {

        // Log::info('GET BLOCKED FRIENDSHIPS');
        // Log::info('fields: ' . json_encode($fields));
        // Log::info('-----------------------------------------------------');

        return $this->getOrPaginate($this->findFriendships(Status::BLOCKED, 'all', ['recipient'], $filterField, $filterQuery), $perPage, $cursor, ['recipient'], $fields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getDeniedFriendships(int $perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::DENIED, 'request', ['sender'], $filterField, $filterQuery), $perPage, $cursor, ['sender'], $fields);
    }

    /**
     * @param  string  $groupSlug
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getAllFriendships(int $perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(null, 'all', ['recipient', 'sender'], $filterField, $filterQuery), $perPage, $cursor, ['recipient', 'sender'], $fields);
    }

    /**
     * @param  string  $groupSlug
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    
    public function getAcceptedFriendships(int $perPage = 0, array $fields = ['*'], string $filterField = 'name', string $filterQuery = '', bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::ACCEPTED, 'request', ['sender'], $filterField, $filterQuery), $perPage, $cursor, ['sender'], $fields);
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  Model  $other
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMutualFriends(Model $other, $perPage = 0, bool $cursor = false)
    {

        // Log::info('GET MUTUAL FRIENDS');
        // Log::info('-----------------------------------------------------');

        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other), $perPage, $cursor, ['recipient', 'sender']);
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends(Model $other, $perPage = 0, bool $cursor = false)
    {

        // Log::info('GET FRIENDS OF FRIENDS');
        // Log::info('-----------------------------------------------------');

        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder($other), $perPage, $cursor, ['recipient', 'sender']);
    }

    /**
     * Get the number of friends
     *
     * @param  string  $groupSlug
     * @param  string  $type
     *
     * @return integer
     */
    public function getFriendsCount()
    {
        // Log::info('GET FRIEDS COUNT');
        // Log::info('-----------------------------------------------------');

        $friendsCount = $this->findFriendships(Status::ACCEPTED)->count();

        return $friendsCount;
    }

    /**
     * Get the number of friends
     *
     * @return integer
     */
    public function getMutualFriendsCount($other)
    {
        // Log::info('GET MUTUAL FRIEDS COUNT');
        // Log::info('-----------------------------------------------------');
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    /**
     * Get the number of friends of friends
     *
     * @return integer
     */
    public function getFriendsOfFriendsCount($other)
    {
        // Log::info('GET FRIENDS OF FRIENDS COUNT');
        // Log::info('other: ' . $other);
        // Log::info('-----------------------------------------------------');

        return $this->friendsOfFriendsQueryBuilder($other)->count();

    }

    protected function getOrPaginate($builder, $perPage, bool $cursor = false, array $with = null, array $fields = ['*'])
    {

        // Log::info('GET PAGINATE');
        // Log::info('builder: ' . json_encode($builder));
        // Log::info('perPage: ' . $perPage);
        // Log::info('cursor: ' . $cursor);
        // Log::info('with: ' . json_encode($with));
        // Log::info('fields: ' . json_encode($fields));
        // Log::info('-----------------------------------------------------');

        if ($perPage == 0) {
            return $builder->select($fields)->get();
        }

        if ($cursor) {
            return $builder
            ->orderBy('updated_at', 'desc')
            ->cursorPaginate($perPage)
            ->through(function ($friendship) use ($with, $fields) {
                return $this->friendshipFields($friendship, $with, $fields);
            });
        }

        return $builder
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage)
        ->through(function ($friendship) use ($with, $fields) {
            return $this->friendshipFields($friendship, $with, $fields);
        });
    }

    private function friendshipFields($friendship, array $with = null, array $fields = ['*']) {

        $friendshipFieldsArray = [];

        foreach (json_decode($friendship, true) as $key => $value) {
            if ((in_array($key, $fields) || in_array('*', $fields)) && !is_array($value)) {
                
                $friendshipFieldsArray[$key] = $friendship->$key;
            }
        }

        if ( ! is_null($with)) {

            $throughSenderArray = [];
            $throughRecipientArray = [];

            if (in_array('sender', $with) && $friendship->sender){

                $senderFields = [];
                foreach (json_decode($friendship->sender, true) as $key => $value) {

                    if ($friendship->sender->$key) { // If key returns a value
                        $senderFields[$key] = $friendship->sender->$key; 
                    } else { // Else try again with key in CamelCase. For relationships table names with underscores
                        $keyCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                        $senderFields[$key] = $friendship->sender->$keyCamelCase;
                    }
                }
                $throughSenderArray['model'] = $senderFields;
            }

            if (in_array('recipient', $with) && $friendship->recipient){
                $recipientFields = [];
                foreach (json_decode($friendship->recipient, true) as $key => $value) {

                    if ($friendship->recipient->$key) { // If key returns a value
                        $recipientFields[$key] = $friendship->recipient->$key; 
                    } else { // Else try again with key in CamelCase. For relationships table name with underscores
                        $keyCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                        $recipientFields[$key] = $friendship->recipient->$keyCamelCase;
                        }
                }
                $throughSenderArray['model'] = $recipientFields;
                
            }

            $modelsFieldsArray = array_merge($throughSenderArray, $throughRecipientArray);

        }

        return array_merge($friendshipFieldsArray, $modelsFieldsArray);

    }

    /**
     * @param        $status
     * @param string $groupSlug
     * @param string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = 'null', string $type = 'all', array $with = null, string $filterField = '', string $filterQuery = '')
    {

        // Log::info('FIND FRIENDSHIPS');
        // Log::info('status: ' . $status);
        // Log::info('filterField: ' . $filterField);
        // Log::info('filterQuery: ' . $filterQuery);
        // Log::info('-----------------------------------------------------');

        $friendshipModelName = Interaction::getFriendshipModelName();
        $selectString = null;

        if ($filterField) {
            $selectString = config('friendships.tables.model') . "." . $filterField;
            $query = $friendshipModelName::select('friendships.*', $selectString);
        } else {
            $query = $friendshipModelName::select('friendships.*');
        }

        $query->leftJoin(config('friendships.tables.model'), function($join) use ($status)
        {
            $join->on(config('friendships.tables.model') . '.id', '=', DB::raw("(
    			CASE 
    			WHEN " . config('friendships.tables.model') . ".id != " . $this->getKey() . " 
                    && " . config('friendships.tables.model') . ".id = friendships.sender_id
    			THEN friendships.sender_id
    			WHEN " . config('friendships.tables.model') . ".id != " . $this->getKey() . " 
                    && " . config('friendships.tables.model') . ".id = friendships.recipient_id
                THEN friendships.recipient_id
    			END)"));

                //if $status is passed, add where clause
                if ( ! is_null($status)) {
                    $join->where('status', $status);
                }
            
        });

        switch ($type) {
            case 'all':
                $query->where(function ($q) {$q->whereRecipient($this);});
                $query->where(config('friendships.tables.model') . '.name', 'LIKE', '%' . $filterQuery . '%');
                $query->orWhere(function ($q) {$q->whereSender($this);});
                $query->where(config('friendships.tables.model') . '.name', 'LIKE', '%' . $filterQuery . '%');
                break;
            case 'pending':
                $query->where(function ($q) {$q->whereSender($this);});
                $query->where(config('friendships.tables.model') . '.name', 'LIKE', '%' . $filterQuery . '%'); 
                break;
            case 'request':
                $query->where(function ($q) {$q->whereRecipient($this);});
               $query->where(config('friendships.tables.model') . '.name', 'LIKE', '%' . $filterQuery . '%');
                break;
        }

        //if $with is passed, add with clause
        if ( ! is_null($with)) {
            $senderArray = [];
            $recipientArray = [];

            if (in_array("sender", $with)){
                $senderArray = 
                ['sender' => function ($query) {
                    $query->where('id', '!=', $this->getKey());
                }];
            }

            if (in_array("recipient", $with)){
                $recipientArray = 
                ['recipient' => function ($query) {
                    $query->where('id', '!=', $this->getKey());
                }];
            }
            $query->with(array_merge($senderArray, $recipientArray));
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getMutualFriendsQueryBuilder(Model $other)
    {
        $user1['friendships'] = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user1['recipients'] = $user1['friendships']->pluck('recipient_id')->all();
        $user1['senders'] = $user1['friendships']->pluck('sender_id')->all();

        $user2['friendships'] = $other->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user2['recipients'] = $user2['friendships']->pluck('recipient_id')->all();
        $user2['senders'] = $user2['friendships']->pluck('sender_id')->all();

        $mutualFriendships = array_unique(
            array_intersect(
                array_merge($user1['recipients'], $user1['senders']),
                array_merge($user2['recipients'], $user2['senders'])
            )
        );
        return $this->whereNotIn('id', [$this->getKey(), $other->getKey()])->whereIn('id', $mutualFriendships);

    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder(Model $other)
    {
        // Log::info('FRIENDS OF FRIENDS QUERYBUILDER');
        // Log::info('other: ' . $other);
        // Log::info('-----------------------------------------------------');

        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        $friendIds = array_unique(array_merge($recipients, $senders));

        $friendshipModelName = Interaction::getFriendshipModelName();
        $fofs = $friendshipModelName::where('status', Status::ACCEPTED)
                          ->where(function ($query) use ($friendIds) {
                              $query->where(function ($q) use ($friendIds) {
                                  $q->whereIn('sender_id', $friendIds);
                              })->orWhere(function ($q) use ($friendIds) {
                                  $q->whereIn('recipient_id', $friendIds);
                              });
                          })
                          // ->whereGroup($this, $groupSlug)
                          ->get(['sender_id', 'recipient_id']);

        $fofIds = array_unique(
            array_merge($fofs->pluck('sender_id')->all(), $fofs->pluck('recipient_id')->all())
        );

//      Alternative way using collection helpers
//        $fofIds = array_unique(
//            $fofs->map(function ($item) {
//                return [$item->sender_id, $item->recipient_id];
//            })->flatten()->all()
//        );


        return $this->whereIn('id', $fofIds)->whereNotIn('id', $friendIds);
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Kimdevylder\Friendships\Models\Friendship|false
     */
    public function befriend(Model $recipient)
    {

        if ( ! $this->canBefriend($recipient)) {
            return false;
        }

        $friendshipModelName = Interaction::getFriendshipModelName();
        $friendship = (new $friendshipModelName)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->friends()->save($friendship);

        Event::dispatch('acq.friendships.sent', [$this, $recipient]);

        return $friendship;

    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {
        Event::dispatch('acq.friendships.cancelled', [$this, $recipient]);

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasFriendRequestFrom(Model $recipient)
    {
        return $this->findFriendship($recipient)->whereSender($recipient)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasSentFriendRequestTo(Model $recipient)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::whereRecipient($recipient)->whereSender($this)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isFriendWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool|int
     */
    public function acceptFriendRequest(Model $recipient)
    {
        Event::dispatch('acq.friendships.accepted', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::ACCEPTED,
        ]);
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool|int
     */
    public function denyFriendRequest(Model $recipient)
    {
        Event::dispatch('acq.friendships.denied', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::DENIED,
        ]);
    }


    /**
     * @param  Model  $friend
     * @param       $groupSlug
     *
     * @return bool
     */
    /* 
    public function groupFriend(Model $friend, $groupSlug)
    {

        $friendship = $this->findFriendship($friend)->whereStatus(Status::ACCEPTED)->first();
        $groupsAvailable = config('friendships.friendships_groups', []);

        if ( ! isset($groupsAvailable[$groupSlug]) || empty($friendship)) {
            return false;
        }

        $group = $friendship->groups()->firstOrCreate([
            'friendship_id' => $friendship->id,
            'group_id' => $groupsAvailable[$groupSlug],
            'friend_id' => $friend->getKey(),
            'friend_type' => $friend->getMorphClass(),
        ]);

        return $group->wasRecentlyCreated;

    } 
    */

    /**
     * @param  Model  $friend
     * @param       $groupSlug
     *
     * @return bool
     */
    /* 
    public function ungroupFriend(Model $friend, $groupSlug = '')
    {

        $friendship = $this->findFriendship($friend)->first();
        $groupsAvailable = config('friendships.friendships_groups', []);

        if (empty($friendship)) {
            return false;
        }

        $where = [
            'friendship_id' => $friendship->id,
            'friend_id' => $friend->getKey(),
            'friend_type' => $friend->getMorphClass(),
        ];

        if ('' !== $groupSlug && isset($groupsAvailable[$groupSlug])) {
            $where['group_id'] = $groupsAvailable[$groupSlug];
        }

        $result = $friendship->groups()->where($where)->delete();

        return $result;

    }
    */

    /**
     * @param  Model  $recipient
     *
     * @return \Kimdevylder\Friendships\Models\Friendship
     */
    public function blockFriend(Model $recipient)
    {
        // if there is a friendship between the two users and the sender is not blocked
        // by the recipient user then delete the friendship
        if ( ! $this->isBlockedBy($recipient)) {
            $this->findFriendship($recipient)->delete();
        }

        $friendshipModelName = Interaction::getFriendshipModelName();
        $friendship = (new $friendshipModelName)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);

        Event::dispatch('acq.friendships.blocked', [$this, $recipient]);

        return $this->friends()->save($friendship);
    }

    /**
     * @param  Model  $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        Event::dispatch('acq.friendships.unblocked', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereSender($this)->delete();
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Kimdevylder\Friendships\Models\Friendship
     */
    public function getFriendship(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasDenied(Model $recipient)
    {
        return $this->findFriendship($recipient)->whereSender($recipient)->whereStatus(Status::DENIED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isDeniedBy(Model $recipient)
    {
        return $recipient->hasDenied($this);
    }


    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friends()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function canBefriend($recipient)
    {
        if (
            $this->hasFriendRequestFrom($recipient)
            || $this->hasSentFriendRequestTo($recipient)
            || $this->isFriendWith($recipient)
            || $this->hasDenied($recipient)
            || $this->isDeniedBy($recipient)
            || $this->hasBlocked($recipient)
        ) {
            return false;
        }
        
        return true;
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendship(Model $recipient)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::betweenModels($this, $recipient);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $this->morphMany($friendshipModelName, 'sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    /* public function groups()
    {
        $friendshipGroupsModelName = Interaction::getFriendshipGroupsModelName();
        return $this->morphMany($friendshipGroupsModelName, 'friend');
    } */
}
