Feature: List elements, from the admin JSON API
  As an administrator, I need to list, from the JSON API, elements (tags, types, contents, posts, items, users, media
  and comments of posts), with a pagination.

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: List tags
    Given a object of type "Teknoo\East\Website\Object\Tag" with id "t1" and '{"name": "Foo", "slug": "foo"}'
    And a object of type "Teknoo\East\Website\Object\Tag" with id "t2" and '{"name": "Bar", "slug": "bar"}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/tags"
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response is the page 1 of 1 with 2 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "@class": "Teknoo\\East\\Website\\Object\\Tag",
            "id": "t2",
            "name": "Bar",
            "slug": "bar"
          },
          {
            "id": "t1",
            "name": "Foo",
            "slug": "foo"
          }
        ]
      }
      """

  Scenario: List types
    Given a type "type1" named "page" with the blocks "body"
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/types"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 1 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "type1",
            "name": "page"
          }
        ]
      }
      """
    And the API response does not contain the key "template" in "data.0"

  Scenario: List contents
    Given a draft content "c1" with the slug "foo" and the title "Foo"
    And it is written by "Max" "Doe" with the email "max@teknoo.software"
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/contents"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 1 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "c1",
            "title": "Foo",
            "slug": "foo",
            "author": {
              "id": "author-id",
              "name": "Max Doe"
            }
          }
        ]
      }
      """
    And the API response does not contain the key "parts" in "data.0"

  Scenario: List posts
    Given a draft post "p1" with the slug "foo" and the title "Foo"
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/posts"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 1 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "@class": "Teknoo\\East\\Website\\Object\\Post",
            "id": "p1",
            "title": "Foo"
          }
        ]
      }
      """

  Scenario: List items
    Given a object of type "Teknoo\East\Website\Doctrine\Object\Item" with id "i1" and '{"name": "Menu", "slug": "menu", "location": "top"}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/items"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 1 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "i1",
            "name": "Menu",
            "location": "top"
          }
        ]
      }
      """

  Scenario: List users
    Given a object of type "Teknoo\East\Common\Object\User" with id "u1" and '{"firstName": "Max", "lastName": "Doe", "email": "max@teknoo.software"}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/users"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 1 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "u1",
            "firstName": "Max",
            "lastName": "Doe",
            "email": "max@teknoo.software"
          }
        ]
      }
      """

  Scenario: List media
    Given a media "m1" named "logo"
    And a media "m2" named "banner"
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/media"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 2 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "m2",
            "name": "banner"
          },
          {
            "id": "m1",
            "name": "logo",
            "metadata": {
              "fileName": "logo.png"
            }
          }
        ]
      }
      """

  Scenario: List comments of a post
    Given a draft post "p1" with the slug "foo" and the title "Foo"
    And it has the comments:
      | id | author | title   | content      |
      | c1 | Max    | Hello   | Nice article |
      | c2 | Bob    | Goodbye | Bad article  |
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/post/p1/comments"
    Then the API response status code is 200
    And the API response is the page 1 of 1 with 2 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "c2",
            "author": "Bob",
            "title": "Goodbye"
          },
          {
            "id": "c1",
            "author": "Max",
            "title": "Hello"
          }
        ]
      }
      """
    And the API response does not contain the key "remoteIp" in "data.0"
