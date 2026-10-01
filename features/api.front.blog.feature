Feature: Read published posts, from the public JSON API
  As a developer, I need to get, from the public JSON API, published posts, with their tags and their public comments,
  and to list published posts, optionally filtered by a tag.

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: Get a published post with its comments
    Given a type "type1" named "article" with the blocks "body"
    And a tag "tag1" named "PHP" with the slug "php"
    And a published post "p1" with the slug "hello-world", the title "Hello World", the parts '{"body": "<img src=x onerror=alert(1)>Hi"}' and the sanitized parts '{"body": "Hi"}'
    And it is tagged with "php"
    And it has the comments:
      | id | author | title   | content      | moderatedAuthor | moderatedTitle | moderatedContent | deleted |
      | c1 | Max    | Hello   | Nice article |                 |                |                  | no      |
      | c2 | Bob    | Spam    | Buy it       | Moderator       | Moderated      | Removed          | no      |
      | c3 | Eve    | Deleted | Deleted      |                 |                |                  | yes     |
    When the API client sends a "GET" request to "https://foo.com/api/v1/post/hello-world"
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "id": "p1",
          "@class": "Teknoo\\East\\Website\\Object\\Content"
        },
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Post",
          "id": "p1",
          "title": "Hello World",
          "slug": "hello-world",
          "parts": {
            "body": "Hi"
          },
          "tags": [
            {
              "@class": "Teknoo\\East\\Website\\Object\\Tag",
              "id": "tag1",
              "name": "PHP",
              "slug": "php",
              "isHighlighted": false
            }
          ],
          "comments": [
            {
              "@class": "Teknoo\\East\\Website\\Object\\Comment",
              "id": "c1",
              "author": "Max",
              "title": "Hello",
              "content": "Nice article",
              "moderated": false
            },
            {
              "id": "c2",
              "author": "Moderator",
              "title": "Moderated",
              "content": "Removed",
              "moderated": true
            }
          ]
        }
      }
      """
    And the API response has 2 elements in "data.comments"
    And the API response does not contain the key "remoteIp" in "data.comments.0"

  Scenario: List published posts, with their sanitized parts
    Given a type "type1" named "article" with the blocks "body"
    And a published post "p1" with the slug "first", the title "First", the parts '{"body": "<script>alert(1)</script>1"}' and the sanitized parts '{"body": "1"}'
    And a published post "p2" with the slug "second", the title "Second", the parts '{"body": "<script>alert(2)</script>2"}' and the sanitized parts '{"body": "2"}'
    When the API client sends a "GET" request to "https://foo.com/api/v1/posts"
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response is the page 1 of 1 with 2 elements
    And the API response contains:
      """
      {
        "data": [
          {
            "id": "p2",
            "title": "Second",
            "slug": "second",
            "parts": {
              "body": "2"
            }
          },
          {
            "id": "p1",
            "title": "First",
            "slug": "first",
            "parts": {
              "body": "1"
            }
          }
        ]
      }
      """
    And the API response does not contain the key "comments" in "data.0"

  Scenario: List published posts of a tag
    Given a type "type1" named "article" with the blocks "body"
    And a tag "tag1" named "PHP" with the slug "php"
    And a published post "p1" with the slug "first", the title "First", the parts '{"body": "1"}' and the sanitized parts '{"body": "1"}'
    And it is tagged with "php"
    When the API client sends a "GET" request to "https://foo.com/api/v1/posts/by/php"
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "totalPages": 1,
          "page": 1,
          "count": 1,
          "tag": {
            "id": "tag1",
            "name": "PHP",
            "slug": "php"
          }
        },
        "data": [
          {
            "id": "p1",
            "title": "First",
            "tags": [
              {
                "slug": "php"
              }
            ]
          }
        ]
      }
      """

  Scenario: Get an unknown post
    When the API client sends a "GET" request to "https://foo.com/api/v1/post/unknown"
    Then the API response is the error 404
