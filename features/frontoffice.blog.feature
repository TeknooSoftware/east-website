Feature: Rendering and return an HTML dynamic post stored into a database server via an HTTP response
  As a developer, I need to render and serve, via a server following the #East programming philosophy, dynamic
  contents, stored into a database server or cluster. And serve it via a HTTP response.
  A dynamic post can host several contents, in different blocks, according to the type of the post.
  Post can have tag and comments. Comment are posted by user or visitor and can be moderated

  Scenario: Render and Serve a list of posts
    Given I have DI initialized
    And I register a router
    And a Content Loader
    And a templating engine
    And a type of post, called "type1" with "2" blocks "block1,block2" and template "Acme:MyBundle:type1.html.twig" with "block1:{block1} block2:{block2}"
    And a Endpoint able to render and serve list of posts.
    And an available post with the slug "foo-bar-1" of type "type1" and tag "tag-1"
    And an available post with the slug "foo-bar-2" of type "type1" and tag "tag-2"
    And an available post with the slug "foo-bar-3" of type "type1" and tag "tag-1"
    And The router can process the request "#/posts/?(?P<tag>[a-zA-Z0-9\.\-]+)?#is" to controller "postsEndPoint"
    When The server will receive the request "https://foo.com/posts"
    Then The client must accept a response
    And I should get "list: foo-bar-3:foo-bar-2:foo-bar-1"

  Scenario: Render and Serve a post
    Given I have DI initialized
    And I register a router
    And a Content Loader
    And a templating engine
    And a Endpoint able to render and serve post.
    And a type of post, called "type1" with "2" blocks "block1,block2" and template "Acme:MyBundle:type1.html.twig" with "block1:{block1} block2:{block2}"
    And an available post with the slug "foo-bar" of type "type1"
    And The router can process the request "#/post/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "postEndPoint"
    When The server will receive the request "https://foo.com/post/foo-bar"
    Then The client must accept a response
    And I should get "block1:hello block2:world"

  Scenario: Render and Serve a sanitized post
    Given I have DI initialized
    And I register a router
    And a Content Loader
    And a templating engine for sanitized post
    And a Endpoint able to render and serve post.
    And a type of post, called "type1" with "2" blocks "block1,block2" and template "Acme:MyBundle:type1.html.twig" with "block1:{block1} block2:{block2}"
    And an available post with the slug "foo-bar" of type "type1"
    And The router can process the request "#/post/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "postEndPoint"
    When The server will receive the request "https://foo.com/post/foo-bar"
    Then The client must accept a response
    And I should get "block1:hello block2:world"

  Scenario: Return 404 page when the post is not available
    Given I have DI initialized
    And I register a router
    And a Content Loader
    And a templating engine
    And a Endpoint able to render and serve post.
    And a type of post, called "type1" with "2" blocks "block1,block2" and template "Acme:MyBundle:type1.html.twig" with "block1{block1} block2{block2}"
    And an available post with the slug "foo-bar" of type "type1"
    And The router can process the request "#/post/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "postEndPoint"
    When The server will receive the request "https://foo.com/post/bar-foo"
    Then The client must accept an error

  Scenario: Return error when the post has an error
    Given I have DI initialized
    And I register a router
    And a Content Loader
    And a templating engine
    And a Endpoint able to render and serve post.
    And a type of post, called "type1" with "2" blocks "block1,block2" and template "Acme:MyBundle:type1.html.twig" with "block1{block1} block2{block2}"
    And an available post with the slug "post-with-error" of type "type1"
    And The router can process the request "#/post/(?P<slug>[a-zA-Z0-9\.\-]+)#is" to controller "postEndPoint"
    When The server will receive the request "https://foo.com/post/post-with-error"
    Then The client must accept an error