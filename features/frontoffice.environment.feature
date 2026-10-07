Feature: Select the environment of the website on the front
  As a developer, I need to prepare new versions of the contents of the website on the same instance, in
  environments visible only when they are selected, via the request parameter `website-env` or the session, and
  restricted to some roles.

  Background:
    Given I have DI initialized
    And the environments definitions:
      | name       | parent  |
      | validation | default |
      | testing    | default |
      | test-a     | testing |
    And the environment "testing" is restricted to the roles "ROLE_TESTER,ROLE_ADMIN"
    And I register a router
    And a Content Loader
    And a templating engine
    And a type of page, called "type1" with "2" blocks "block1,block2" and template "Acme:MyBundle:type1.html.twig" with "block1:{block1} block2:{block2}"
    And an available page with the slug "public-page" of type "type1"
    And an available page with the slug "validation-page" of type "type1" in the environment "validation"
    And an available page with the slug "testing-page" of type "type1" in the environment "testing"
    And an available page with the slug "test-a-page" of type "type1" in the environment "test-a"

  Scenario: Serve a page of the default environment without selection
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/public-page"
    Then The client must accept a response
    And I should get "block1:hello block2:world"

  Scenario: A page of another environment is not available without selection
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/validation-page"
    Then The client must accept an error with the code 404

  Scenario: Select a public environment with the request parameter and store it into the session
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And a session storage
    When The server will receive the request "https://foo.com/page/validation-page?website-env=validation"
    Then The client must accept a response
    And I should get "block1:hello block2:world"
    And the session must contain the environment "validation"

  Scenario: Select a public environment with the request parameter without session
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/validation-page?website-env=validation"
    Then The client must accept a response
    And I should get "block1:hello block2:world"

  Scenario: A page of the default environment is available from another environment
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/public-page?website-env=validation"
    Then The client must accept a response
    And I should get "block1:hello block2:world"

  Scenario: A page of a parent environment is available from a child environment
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/testing-page?website-env=test-a"
    Then The client must accept a response
    And I should get "block1:hello block2:world"

  Scenario: A page of a child environment is not available from its parent environment
    Given an authenticated user with the roles "ROLE_TESTER"
    And a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/test-a-page?website-env=testing"
    Then The client must accept an error with the code 404

  Scenario: A page of a sibling environment is not available
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/validation-page?website-env=test-a"
    Then The client must accept an error with the code 404

  Scenario: An unknown environment is an error 404 and is not stored into the session
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And a session storage
    When The server will receive the request "https://foo.com/page/public-page?website-env=unknown"
    Then The client must accept an error with the code 404
    And the session must not contain an environment

  Scenario: A restricted environment is an error 404 for an anonymous visitor
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And a session storage
    When The server will receive the request "https://foo.com/page/testing-page?website-env=testing"
    Then The client must accept an error with the code 404
    And the session must not contain an environment

  Scenario: A restricted environment is an error 404 for a user without a required role
    Given an authenticated user with the roles "ROLE_USER"
    And a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    When The server will receive the request "https://foo.com/page/testing-page?website-env=testing"
    Then The client must accept an error with the code 404

  Scenario: A restricted environment is available for a user with a required role
    Given an authenticated user with the roles "ROLE_USER,ROLE_TESTER"
    And a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And a session storage
    When The server will receive the request "https://foo.com/page/testing-page?website-env=testing"
    Then The client must accept a response
    And I should get "block1:hello block2:world"
    And the session must contain the environment "testing"

  Scenario: The environment stored into the session is used when the parameter is absent
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And the session contains the environment "validation"
    When The server will receive the request "https://foo.com/page/validation-page"
    Then The client must accept a response
    And I should get "block1:hello block2:world"
    And the session must contain the environment "validation"

  Scenario: The request parameter replaces the environment stored into the session
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And the session contains the environment "validation"
    When The server will receive the request "https://foo.com/page/test-a-page?website-env=test-a"
    Then The client must accept a response
    And I should get "block1:hello block2:world"
    And the session must contain the environment "test-a"

  Scenario: An environment stored into the session, no longer defined, is removed and the default environment is used
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And the session contains the environment "removed"
    When The server will receive the request "https://foo.com/page/public-page"
    Then The client must accept a response
    And I should get "block1:hello block2:world"
    And the session must not contain an environment

  Scenario: A restricted environment stored into the session is removed for a visitor without the required role
    Given a Endpoint able to render and serve page.
    And The router can process the request "#/page/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "contentEndPoint"
    And the session contains the environment "testing"
    When The server will receive the request "https://foo.com/page/testing-page"
    Then The client must accept an error with the code 404
    And the session must not contain an environment
