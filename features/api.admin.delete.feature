Feature: Delete an element, from the admin JSON API
  As an administrator, I need to delete, from the JSON API, elements (tags, types, contents, posts, items, users,
  media and comments of posts), with the method DELETE or POST. The deleted element is returned in the response.

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: Delete a tag with the method DELETE
    Given a object of type "Teknoo\East\Website\Object\Tag" with id "t1" and '{"name": "Foo", "slug": "foo"}'
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/tag/t1/delete"
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response is:
      """
      {
        "meta": {
          "id": "t1",
          "@class": "Teknoo\\East\\Website\\Object\\Tag",
          "deleted": "success"
        },
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Tag",
          "id": "t1",
          "name": "Foo",
          "slug": "foo"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete a type with the method POST
    Given a object of type "Teknoo\East\Website\Object\Type" with id "type1" and '{"name": "Page"}'
    When the API client sends a "POST" request to "https://foo.com/api/v1/admin/type/type1/delete"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "id": "type1",
          "deleted": "success"
        },
        "data": {
          "id": "type1",
          "name": "Page"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete a content
    Given a draft content "c1" with the slug "foo" and the title "Foo"
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/content/c1/delete"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "id": "c1",
          "deleted": "success"
        },
        "data": {
          "id": "c1",
          "title": "Foo",
          "slug": "foo"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete a post
    Given a draft post "p1" with the slug "foo" and the title "Foo"
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/post/p1/delete"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "id": "p1",
          "deleted": "success"
        },
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Post",
          "id": "p1",
          "title": "Foo",
          "slug": "foo"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete an item
    Given a object of type "Teknoo\East\Website\Doctrine\Object\Item" with id "i1" and '{"name": "Menu", "slug": "menu"}'
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/item/i1/delete"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "id": "i1",
          "deleted": "success"
        },
        "data": {
          "id": "i1",
          "name": "Menu",
          "slug": "menu"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete a user
    Given a object of type "Teknoo\East\Common\Object\User" with id "u1" and '{"firstName": "Max", "lastName": "Doe", "email": "max@teknoo.software"}'
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/user/u1/delete"
    Then the API response status code is 200
    And the API response is:
      """
      {
        "meta": {
          "id": "u1",
          "@class": "Teknoo\\East\\Common\\Object\\User",
          "deleted": "success"
        },
        "data": {
          "@class": "Teknoo\\East\\Common\\Object\\User",
          "id": "u1",
          "email": "max@teknoo.software"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete a media
    Given a media "m1" named "logo"
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/media/m1/delete"
    Then the API response status code is 200
    And the API response is:
      """
      {
        "meta": {
          "id": "m1",
          "@class": "Teknoo\\East\\Common\\Object\\Media",
          "deleted": "success"
        },
        "data": {
          "@class": "Teknoo\\East\\Common\\Object\\Media",
          "id": "m1",
          "name": "logo"
        }
      }
      """

  Scenario: Delete a comment of a post
    Given a draft post "p1" with the slug "foo" and the title "Foo"
    And it has the comments:
      | id | author | title | content      |
      | c1 | Max    | Hello | Nice article |
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/post/p1/comment/c1/delete"
    Then the API response status code is 200
    And the API response is:
      """
      {
        "meta": {
          "id": "c1",
          "@class": "Teknoo\\East\\Website\\Object\\Comment",
          "deleted": "success"
        },
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Comment",
          "id": "c1",
          "title": "Hello"
        }
      }
      """
    And the last object updated must be deleted

  Scenario: Delete an unknown tag
    When the API client sends a "DELETE" request to "https://foo.com/api/v1/admin/tag/unknown/delete"
    Then the API response is the error 404
