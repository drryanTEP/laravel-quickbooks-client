<?php

namespace Spinen\QuickBooks;

use App\school;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\Exception\SdkException;

/**
 * Class Token
 *
 * @package Spinen\QuickBooks
 *
 * @property boolean $hasValidAccessToken Is the access token valid
 * @property boolean $hasValidRefreshToken Is the refresh token valid
 * @property Carbon $access_token_expires_at Timestamp that the access token expires
 * @property Carbon $refresh_token_expires_at Timestamp that the refresh token expires
 * @property integer $school_id Id of the related school
 * @property string $access_token The access token
 * @property string $realm_id Realm Id from the OAuth token
 * @property string $refresh_token The refresh token
 * @property school $school
 */
class Token extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quickbooks_tokens';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'access_token_expires_at',
        'refresh_token_expires_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'access_token',
        'access_token_expires_at',
        'realm_id',
        'refresh_token',
        'refresh_token_expires_at',
        'school_id',
    ];

    /**
     * Check if access token is valid
     *
     * A token is good for 1 hour, so if it expires greater than 1 hour from now, it is still valid
     *
     * @return bool
     */
    public function getHasValidAccessTokenAttribute()
    {
        return $this->access_token_expires_at && Carbon::now()
                                                       ->lt($this->access_token_expires_at);
    }

    /**
     * Check if refresh token is valid
     *
     * A token is good for 101 days, so if it expires greater than 101 days from now, it is still valid
     *
     * @return bool
     */
    public function getHasValidRefreshTokenAttribute()
    {
        return $this->refresh_token_expires_at && Carbon::now()
                                                        ->lt($this->refresh_token_expires_at);
    }

    /**
     * Parse OauthToken.
     *
     * Process the OAuth token & store it in the persistent storage
     *
     * @param OAuth2AccessToken $oauth_token
     *
     * @return Token
     * @throws SdkException
     */
    public function parseOauthToken(OAuth2AccessToken $oauth_token)
    {
        // TODO: Deal with exception
        $this->access_token = $oauth_token->getAccessToken();
        $this->access_token_expires_at = Carbon::parse($oauth_token->getAccessTokenExpiresAt());
        $this->realm_id = $oauth_token->getRealmID();
        $this->refresh_token = $oauth_token->getRefreshToken();
        $this->refresh_token_expires_at = Carbon::parse($oauth_token->getRefreshTokenExpiresAt());

        return $this;
    }

    /**
     * Remove the token
     *
     * When a token is deleted, we still need a token for the client for the school.
     *
     * @return Token
     * @throws Exception
     */
    public function remove()
    {
        $school = $this->school;

        $this->delete();

        return $school->quickBooksToken()
                    ->make();
    }

    /**
     * Belongs to school.
     *
     * @return BelongsTo
     */
    public function school()
    {
        $config = config('quickbooks.school');

        return $this->belongsTo($config['model'], $config['keys']['foreign'], $config['keys']['owner']);
    }
}
