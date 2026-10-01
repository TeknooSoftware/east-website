Feature: Read and update an element, from the admin JSON API
  As an administrator, I need to read and update, from the JSON API, elements (tags, types, contents, posts, items,
  users and media), with a JSON body (partial update) or an urlencoded body, and moderate comments of posts.

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: Get a tag
    Given a object of type "Teknoo\East\Website\Object\Tag" with id "t1" and '{"name": "Foo", "slug": "foo"}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/tag/t1"
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "id": "t1",
          "@class": "Teknoo\\East\\Website\\Object\\Tag"
        },
        "data": {
          "id": "t1",
          "name": "Foo",
          "slug": "foo",
          "isHighlighted": false
        }
      }
      """

  Scenario: Update partially a tag with a JSON body
    Given a object of type "Teknoo\East\Website\Object\Tag" with id "t1" and '{"name": "Foo", "slug": "foo"}'
    When the API client sends a "PUT" JSON request to "https://foo.com/api/v1/admin/tag/t1" with:
      """
      {
        "isHighlighted": true
      }
      """
    Then the API response status code is 200
    And An object "t1" must be updated
    And the API response contains:
      """
      {
        "data": {
          "id": "t1",
          "name": "Foo",
          "slug": "foo",
          "isHighlighted": true
        }
      }
      """

  Scenario: Update a tag with an urlencoded body
    Given a object of type "Teknoo\East\Website\Object\Tag" with id "t1" and '{"name": "Foo", "slug": "foo"}'
    When the API client sends a "POST" form request to "https://foo.com/api/v1/admin/tag/t1" with "tag%5Bname%5D=Bar&tag%5Bslug%5D=foo"
    Then the API response status code is 200
    And An object "t1" must be updated
    And the API response contains:
      """
      {
        "data": {
          "id": "t1",
          "name": "Bar",
          "slug": "foo"
        }
      }
      """

  Scenario: Update blocks of a content with a JSON body
    Given a type "type1" named "page" with the blocks "body,header"
    And a draft content "c1" with the slug "foo" and the title "Foo"
    When the API client sends a "PUT" JSON request to "https://foo.com/api/v1/admin/content/c1" with:
      """
      {
        "block_body": "<p>Hello</p>",
        "block_header": "World"
      }
      """
    Then the API response status code is 200
    And An object "c1" must be updated
    And the API response contains:
      """
      {
        "data": {
          "id": "c1",
          "title": "Foo",
          "slug": "foo",
          "parts": {
            "body": "<p>Hello</p>",
            "header": "World"
          },
          "type": {
            "id": "type1",
            "name": "page"
          }
        }
      }
      """

  Scenario: Publish a draft post with a JSON body
    Given a draft post "p1" with the slug "foo" and the title "Foo"
    When the API client sends a "PUT" JSON request to "https://foo.com/api/v1/admin/post/p1" with:
      """
      {
        "subtitle": "Bar",
        "publish": true
      }
      """
    Then the API response status code is 200
    And the post "p1" must be published
    And the API response contains:
      """
      {
        "data": {
          "id": "p1",
          "title": "Foo",
          "subtitle": "Bar"
        }
      }
      """

  Scenario: Get an item
    Given a object of type "Teknoo\East\Website\Doctrine\Object\Item" with id "i1" and '{"name": "Menu", "slug": "menu", "location": "top"}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/item/i1"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "id": "i1",
          "name": "Menu",
          "slug": "menu",
          "location": "top",
          "hidden": false
        }
      }
      """

  Scenario: Get a user, without its password
    Given a object of type "Teknoo\East\Common\Object\User" with id "u1" and '{"firstName": "Max", "lastName": "Doe", "email": "max@teknoo.software", "roles": ["ROLE_USER"]}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/user/u1"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "id": "u1",
          "firstName": "Max",
          "lastName": "Doe",
          "email": "max@teknoo.software",
          "roles": ["ROLE_USER"]
        }
      }
      """
    And the API response does not contain the key "authData" in "data"

  Scenario: Update a user with an invalid role, errors not bubbled to the root form are not returned, only the user
    Given a object of type "Teknoo\East\Common\Object\User" with id "u1" and '{"firstName": "Max", "lastName": "Doe", "email": "max@teknoo.software", "roles": ["ROLE_USER"]}'
    When the API client sends a "PUT" JSON request to "https://foo.com/api/v1/admin/user/u1" with:
      """
      {
        "roles": ["ROLE_BAD"]
      }
      """
    Then the API response status code is 400
    And the API response contains:
      """
      {
        "meta": {
          "id": "u1"
        },
        "data": {
          "id": "u1",
          "firstName": "Max"
        }
      }
      """

  Scenario: Get a media
    Given a media "m1" named "logo"
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/media/m1"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "id": "m1",
          "@class": "Teknoo\\East\\Common\\Object\\Media"
        },
        "data": {
          "id": "m1",
          "name": "logo",
          "length": 123,
          "metadata": {
            "contentType": "image/png",
            "fileName": "logo.png",
            "alternative": "Alt logo"
          }
        }
      }
      """

  Scenario: Moderate a comment of a post with a JSON body
    Given a draft post "p1" with the slug "foo" and the title "Foo"
    And it has the comments:
      | id | author | title   | content      |
      | c1 | Max    | Hello   | Nice article |
    When the API client sends a "PUT" JSON request to "https://foo.com/api/v1/admin/post/p1/comment/c1" with:
      """
      {
        "moderatedAuthor": "Moderator",
        "moderatedTitle": "Moderated",
        "moderatedContent": "Moderated content"
      }
      """
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "id": "c1",
          "author": "Max",
          "title": "Hello",
          "content": "Nice article",
          "moderatedAuthor": "Moderator",
          "moderatedTitle": "Moderated",
          "moderatedContent": "Moderated content"
        }
      }
      """

  Scenario: Get an unknown tag
    When the API client sends a "GET" request to "https://foo.com/api/v1/admin/tag/unknown"
    Then the API response is the error 404
