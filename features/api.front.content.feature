Feature: Read a published content, from the public JSON API
  As a developer, I need to get, from the public JSON API, published contents, with sanitized parts, without private
  data (author's email, template and raw parts).

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: Get a published content
    Given a type "type1" named "page" with the blocks "body,header"
    And a published content "c1" with the slug "about", the title "About", the parts '{"body": "<script>alert(1)</script><p>Hello</p>", "header": "Welcome"}' and the sanitized parts '{"body": "<p>Hello</p>", "header": "Welcome"}'
    And it is written by "Max" "Doe" with the email "max@teknoo.software"
    When the API client sends a "GET" request to "https://foo.com/api/v1/content/about"
    Then the API response status code is 200
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "id": "c1",
          "@class": "Teknoo\\East\\Website\\Object\\Content"
        },
        "data": {
          "@class": "Teknoo\\East\\Website\\Object\\Content",
          "id": "c1",
          "title": "About",
          "slug": "about",
          "author": {
            "id": "author-id",
            "name": "Max Doe"
          },
          "type": {
            "@class": "Teknoo\\East\\Website\\Object\\Type",
            "id": "type1",
            "name": "page",
            "blocks": [
              {
                "name": "body",
                "type": "text"
              },
              {
                "name": "header",
                "type": "text"
              }
            ]
          },
          "tags": [],
          "parts": {
            "body": "<p>Hello</p>",
            "header": "Welcome"
          }
        }
      }
      """
    And the API response does not contain the key "email" in "data.author"
    And the API response does not contain the key "template" in "data.type"
    And the API response does not contain the key "createdAt" in "data"

  Scenario: Get an unknown content
    When the API client sends a "GET" request to "https://foo.com/api/v1/content/unknown"
    Then the API response is the error 404
