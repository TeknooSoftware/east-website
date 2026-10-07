Feature: Create an element, from the admin JSON API
  As an administrator, I need to create, from the JSON API, elements (tags, types, contents, posts, items, users and
  media), with a JSON body or an urlencoded body. After the creation, the client is redirected to the element.

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: Create a tag with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/tag/new" with:
      """
      {
        "name": "Foo Bar",
        "slug": "foo-bar",
        "isHighlighted": true
      }
      """
    Then It is redirect to "/api/v1/admin/tag/[a-zA-Z0-9]+"
    And An object "Tag" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "@class": "Teknoo\\East\\Website\\Object\\Tag"
        },
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Tag",
          "name": "Foo Bar",
          "slug": "foo-bar",
          "isHighlighted": true
        }
      }
      """

  Scenario: Create a tag with an urlencoded body
    When the API client sends a "POST" form request to "https://foo.com/api/v1/admin/tag/new" with "tag%5Bname%5D=Foo&tag%5Bslug%5D=foo"
    Then It is redirect to "/api/v1/admin/tag/[a-zA-Z0-9]+"
    And An object "Tag" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "name": "Foo",
          "slug": "foo",
          "isHighlighted": false
        }
      }
      """

  Scenario: Create a type with a JSON body (the type of a block is the index of the choice)
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/type/new" with:
      """
      {
        "name": "Page",
        "template": "page.html.twig",
        "blocks": [
          {
            "name": "body",
            "type": "2"
          }
        ]
      }
      """
    Then It is redirect to "/api/v1/admin/type/[a-zA-Z0-9]+"
    And An object "Type" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "name": "Page",
          "template": "page.html.twig",
          "blocks": [
            {
              "name": "body",
              "type": "text"
            }
          ]
        }
      }
      """

  Scenario: Create a content with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/content/new" with:
      """
      {
        "title": "Foo",
        "subtitle": "Bar"
      }
      """
    Then It is redirect to "/api/v1/admin/content/[a-zA-Z0-9]+"
    And An object "Content" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Content",
          "title": "Foo",
          "subtitle": "Bar",
          "slug": "foo",
          "publishedAt": null,
          "parts": [],
          "tags": [],
          "environment": "default"
        }
      }
      """

  Scenario: Create a content in an environment with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/content/new" with:
      """
      {
        "title": "Foo",
        "subtitle": "Bar",
        "environment": "validation"
      }
      """
    Then It is redirect to "/api/v1/admin/content/[a-zA-Z0-9]+"
    And An object "Content" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Content",
          "title": "Foo",
          "slug": "foo",
          "environment": "validation"
        }
      }
      """

  Scenario: Create a content in an unknown environment with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/content/new" with:
      """
      {
        "title": "Foo",
        "subtitle": "Bar",
        "environment": "unknown"
      }
      """
    Then the API response status code is 400
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "@class": "Teknoo\\East\\Website\\Object\\Content"
        },
        "data": {
          "title": "Foo",
          "environment": "default"
        }
      }
      """
    And there are 0 objects "Content" persisted

  Scenario: Create and publish a post with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/post/new" with:
      """
      {
        "title": "Foo",
        "subtitle": "Bar",
        "publish": true
      }
      """
    Then It is redirect to "/api/v1/admin/post/[a-zA-Z0-9]+"
    And An object "Post" must be persisted
    And the created "Post" must be published
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Post",
          "title": "Foo",
          "subtitle": "Bar",
          "slug": "foo"
        }
      }
      """

  Scenario: Create an item with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/item/new" with:
      """
      {
        "name": "Menu",
        "location": "top",
        "position": 2,
        "hidden": false,
        "environment": "testing"
      }
      """
    Then It is redirect to "/api/v1/admin/item/[a-zA-Z0-9]+"
    And An object "Item" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "name": "Menu",
          "slug": "menu",
          "location": "top",
          "position": 2,
          "hidden": false,
          "content": null,
          "parent": null,
          "environment": "testing"
        }
      }
      """

  Scenario: Create a user with a JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/user/new" with:
      """
      {
        "firstName": "Max",
        "lastName": "Doe",
        "email": "max@teknoo.software",
        "roles": ["ROLE_ADMIN"],
        "active": true
      }
      """
    Then It is redirect to "/api/v1/admin/user/[a-zA-Z0-9]+"
    And An object "User" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "firstName": "Max",
          "lastName": "Doe",
          "email": "max@teknoo.software",
          "roles": ["ROLE_ADMIN"],
          "active": true
        }
      }
      """

  Scenario: Upload a media with a multipart body
    When the API client uploads the file "logo.png" in the field "image" of the form "media" to "https://foo.com/api/v1/admin/media/new" with "media%5Bname%5D=Logo&media%5Balternative%5D=Our%20logo"
    Then It is redirect to "/api/v1/admin/media/[a-zA-Z0-9]+"
    And An object "Media" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "data": {
          "name": "Logo",
          "metadata": {
            "contentType": "image/png",
            "fileName": "logo.png",
            "alternative": "Our logo"
          }
        }
      }
      """
    And the API response does not contain the key "localPath" in "data.metadata"

  Scenario: Create a type with an invalid block, errors not bubbled to the root form are not returned, only the object
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/type/new" with:
      """
      {
        "name": "Page",
        "template": "page.html.twig",
        "blocks": [
          {
            "name": "body",
            "type": "bad"
          }
        ]
      }
      """
    Then the API response status code is 400
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "@class": "Teknoo\\East\\Website\\Object\\Type"
        },
        "data": {
          "name": "Page",
          "template": "page.html.twig"
        }
      }
      """
    And there are 0 objects "Type" persisted

  Scenario: Create a tag with a malformed JSON body
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/admin/tag/new" with:
      """
      {"name": "Foo
      """
    Then the API response is the error 400
