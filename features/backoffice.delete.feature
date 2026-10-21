Feature: Delete an element, from the dbms server via an HTTP request
  Routes deleting elements check the CSRF token "teknoo_east_website_admin_delete", sent in the field `_token` of the
  body or of the query string. An invalid or a missing token returns a 403 error.

  Scenario: Delete a type
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Object\Type" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/type/delete/foo" with the CSRF token "teknoo_east_website_admin_delete"
    Then The client must accept a response
    And It is redirect to "/admin/types"
    And the last object updated must be deleted

  Scenario: Delete a content
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Content" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/content/delete/foo" with the CSRF token "teknoo_east_website_admin_delete"
    Then The client must accept a response
    And It is redirect to "/admin/contents"
    And the last object updated must be deleted

  Scenario: Delete a post
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Post" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/post/delete/foo" with the CSRF token "teknoo_east_website_admin_delete"
    Then The client must accept a response
    And It is redirect to "/admin/posts"
    And the last object updated must be deleted

  Scenario: Delete an item
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Item" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/item/delete/foo" with the CSRF token "teknoo_east_website_admin_delete"
    Then The client must accept a response
    And It is redirect to "/admin/items"
    And the last object updated must be deleted

  Scenario: Delete a tag from a link, with the CSRF token in the query string
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Object\Tag" with id "foo"
    When Symfony will receive the GET request "https://foo.com/admin/tag/delete/foo" with the CSRF token "teknoo_east_website_admin_delete"
    Then The client must accept a response
    And It is redirect to "/admin/tags"
    And the last object updated must be deleted

  Scenario: Delete a comment of a post
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a draft post "p1" with the slug "foo" and the title "Foo"
    And it has the comments:
      | id | author | title | content      |
      | c1 | Max    | Hello | Nice article |
    When Symfony will receive the DELETE request "https://foo.com/admin/post/p1/comment/delete/c1" with the CSRF token "teknoo_east_website_admin_delete"
    Then The client must accept a response
    And It is redirect to "/admin/post/p1/comments"
    And the last object updated must be deleted

  Scenario: Refuse to delete a type without CSRF token
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Object\Type" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/type/delete/foo"
    Then the API response status code is 403
    And no object must be deleted

  Scenario: Refuse to delete a type with an invalid CSRF token
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Object\Type" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/type/delete/foo?_token=bad"
    Then the API response status code is 403
    And no object must be deleted

  Scenario: Refuse to delete a type with the CSRF token of another form
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Object\Type" with id "foo"
    When Symfony will receive the DELETE request "https://foo.com/admin/type/delete/foo" with the CSRF token "teknoo_east_common_admin_delete"
    Then the API response status code is 403
    And no object must be deleted

  Scenario: Refuse to delete a comment of a post without CSRF token
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a draft post "p1" with the slug "foo" and the title "Foo"
    And it has the comments:
      | id | author | title | content      |
      | c1 | Max    | Hello | Nice article |
    When Symfony will receive the DELETE request "https://foo.com/admin/post/p1/comment/delete/c1"
    Then the API response status code is 403
    And no object must be deleted
