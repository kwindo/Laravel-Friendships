<?php
namespace Kimdevylder\Friendships\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Kimdevylder\Friendships\Interaction;
// use Kimdevylder\Friendships\Models\FriendFriendshipGroups;
use Kimdevylder\Friendships\Models\Friendship;
use Kimdevylder\Friendships\Status;

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
    public function getFriends($perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships( Status::ACCEPTED, 'all', ['recipient', 'sender']), $perPage, $cursor, ['recipient', 'sender'], $fields, $modelFields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getPendingFriendships(int $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::PENDING, 'pending', ['recipient'])->where('sender_id', $this->id), $perPage, $cursor, ['recipient'], $fields, $modelFields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getFriendshipRequests(int $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::PENDING, 'request', ['sender'])->where('recipient_id', $this->id), $perPage, $cursor, ['sender'], $fields, $modelFields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getBlockedFriendships(int $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::BLOCKED, 'all', ['recipient']), $perPage, $cursor, ['recipient'], $fields, $modelFields);
    }

    /**
     * @param  int  $perPage  Number
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getDeniedFriendships(int $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::DENIED, 'request', ['sender']), $perPage, $cursor, ['sender'], $fields, $modelFields);
    }

    /**
     * @param  string  $groupSlug
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getAllFriendships(int $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(null, 'all', ['recipient', 'sender']), $perPage, $cursor, ['recipient', 'sender'], $fields, $modelFields);
    }

    /**
     * @param  string  $groupSlug
     * @param  int  $perPage  Number
     * @param  array  $fields
     * @param  string $type
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getAcceptedFriendships(int $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->findFriendships(Status::ACCEPTED, 'request', ['sender']), $perPage, $cursor, ['sender'], $fields, $modelFields);
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
    public function getMutualFriends(Model $other, $perPage = 0, array $fields = ['*'], array $modelFields = ['*'], bool $cursor = false)
    {
        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other, $fields), $perPage, $cursor, ['recipient', 'sender'], $fields, $modelFields);
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
    public function getFriendsOfFriends($perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder(), $perPage, $fields);
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
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    protected function getOrPaginate($builder, $perPage, bool $cursor = false, array $with = null, array $fields = ['*'], array $modelFields = ['*'])
    {
        if ($perPage == 0) {
            return $builder->select($fields)->get();
        }

        if ($cursor) {
            return $builder
            ->orderBy('updated_at', 'desc')
            ->cursorPaginate($perPage)
            ->through(function ($friendship) use ($with, $fields, $modelFields) {
                return $this->friendshipFields($friendship, $with, $fields, $modelFields);
            });
        }

        return $builder
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage)
        ->through(function ($friendship) use ($with, $fields, $modelFields) {
            return $this->friendshipFields($friendship, $with, $fields, $modelFields);
        });
    }

    private function friendshipFields($friendship, array $with = null, array $fields = ['*'], array $modelFields = ['*']) {

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
                    if (in_array($key, $modelFields) || in_array('*', $modelFields)) {

                        if ($friendship->sender->$key) { // If key returns a value
                            $senderFields[$key] = $friendship->sender->$key; 
                        } else { // Else try again with key in CamelCase. For relationships table names with underscores
                            $keyCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                            $senderFields[$key] = $friendship->sender->$keyCamelCase;
                        }

                    }
                }
                $throughSenderArray['model'] = $senderFields;
            }

            if (in_array('recipient', $with) && $friendship->recipient){
                $recipientFields = [];
                foreach (json_decode($friendship->recipient, true) as $key => $value) {
                    if (in_array($key, $modelFields) || in_array('*', $modelFields)) {

                        if ($friendship->recipient->$key) { // If key returns a value
                            $recipientFields[$key] = $friendship->recipient->$key; 
                        } else { // Else try again with key in CamelCase. For relationships table name with underscores
                            $keyCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                            $recipientFields[$key] = $friendship->recipient->$keyCamelCase;
                        }
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
    private function findFriendships($status = null, string $type = 'all', array $with = null)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();

        $query = $friendshipModelName::where(function ($query) use ($type) {
            switch ($type) {
                case 'all':
                    $query->where(function ($q) {$q->whereSender($this);})->orWhere(function ($q) {$q->whereRecipient($this);});
                    break;
                case 'pending':
                    $query->where(function ($q) {$q->whereSender($this);});
                    break;
                case 'request':
                    $query->where(function ($q) {$q->whereRecipient($this);});
                    break;
            }
        });

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

        //if $status is passed, add where clause
        if ( ! is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getMutualFriendsQueryBuilder(Model $other, array $fields = ['*'])
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
    private function friendsOfFriendsQueryBuilder($groupSlug = '')
    {
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
                          ->whereGroup($this, $groupSlug)
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
            ! $this->hasFriendRequestFrom($recipient)
            && ! $this->hasSentFriendRequestTo($recipient)
            && ! $this->isFriendWith($recipient)
            && ! $this->hasDenied($recipient)
            && ! $this->isDeniedBy($recipient)
            && ! $this->hasBlocked($recipient)
        ) {
            return false;
        } else {
            return true;
        }
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
