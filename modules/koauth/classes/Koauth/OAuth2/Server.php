<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Wrapper for OAuth2_Server
 * 
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Koauth
 * @copyright  2013 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */
class Koauth_OAuth2_Server extends OAuth2_Server
{
	/**
	 * Overriding the Constructor to add our own default
	 * 
	 * @param mixed $storage
	 * array - array of Objects to implement storage
	 * OAuth2_Storage object implementing all required storage types (ClientCredentialsInterface and AccessTokenInterface as a minimum)
	 *
	 * @param array $config
	 * specify a different token lifetime, token header name, etc
	 *
	 * @param array $grantTypes
	 * An array of OAuth2_GrantTypeInterface to use for granting access tokens
	 *
	 * @param array $responseTypes
	 * Response types to use.  array keys should be "code" and and "token" for
	 * Access Token and Authorization Code response types
	 *
	 * @param OAuth2_TokenTypeInterface $tokenType
	 * The token type object to use. Valid token types are "bearer" and "mac"
	 *
	 * @param OAuth2_ScopeInterface $scopeUtil
	 * The scope utility class to use to validate scope
	 *
	 * @param OAuth2_ClientAssertionTypeInterface $clientAssertionType
	 * The method in which to verify the client identity.  Default is HttpBasic
	 *
	 * @return
	 * TRUE if everything in required scope is contained in available scope,
	 * and FALSE if it isn't.
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-7
	 *
	 * @ingroup oauth2_section_7
	 */
	public function __construct(
			$storage = array(),
			array $config = array(),
			array $grantTypes = array(),
			array $responseTypes = array(),
			OAuth2_TokenTypeInterface $tokenType = null,
			OAuth2_ScopeInterface $scopeUtil = null,
			OAuth2_ClientAssertionTypeInterface $clientAssertionType = null
		)
	{
		if (empty($storage))
		{
			$storage = new OAuth2_Storage_ORM();
		}

		if (empty($config))
		{
			$config = array(
				'access_lifetime'          => Kohana::$config->load('koauth.access_lifetime'),
				'www_realm'                => Kohana::$config->load('koauth.www_realm'),
				//'token_param_name'         => 'access_token',
				//'token_bearer_header_name' => 'Bearer',
				'enforce_state'            => TRUE,
				'allow_implicit'           => TRUE,
			);
		}
		
		if (empty($grantTypes))
		{
			// Add grant types
			$grantTypes = array(
				new OAuth2_GrantType_UserCredentials($storage),
				new OAuth2_GrantType_AuthorizationCode($storage),
				new OAuth2_GrantType_ClientCredentials($storage),
				new OAuth2_GrantType_RefreshToken($storage)
			);
		}
		
		if ($scopeUtil == NULL)
		{
			// Configure your available scopes
			$memory = new OAuth2_Storage_Memory(array(
				'default_scope' => Kohana::$config->load('koauth.default_scope'),
				'supported_scopes' => Kohana::$config->load('koauth.supported_scopes')
			));
			$scopeUtil = new OAuth2_Scope($memory);
		}
		
		parent::__construct($storage, $config, $grantTypes, $responseTypes, $tokenType, $scopeUtil, $clientAssertionType);
	}
	
	public function processResponse(Kohana_Response &$koresponse)
	{
		if ($this->response instanceof OAuth2_Response)
		{
			if ($this->response->isClientError() OR $this->response->isServerError())
			{
				throw new OAuth2_Exception($this->response);
			}
			else
			{
				$koresponse->body($this->response->getResponseBody());
				$koresponse->headers($this->response->getHttpHeaders());
				$koresponse->headers('Content-Type', 'application/json');
				$koresponse->status($this->response->getStatusCode());
			}
		}
	}
}
