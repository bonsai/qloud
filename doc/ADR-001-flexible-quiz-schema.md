# ADR 001: Flexible Multi-Directional Quiz Data Structure

## Status
Proposed

## Context
The current quiz application (`quiz.html`) relies on `aws.json` which contains only text data (service names, descriptions). Image data exists separately in `img.tree.json`.
The user wants to enable "multi-directional" quizzes, meaning:
1.  **Text -> Image**: Show "EC2", choose correct icon.
2.  **Image -> Text**: Show EC2 icon, choose "EC2".
3.  **Text -> Text**: Show Description, choose Service Name.
4.  **Image -> Image**: (Potential future use).

To support this, we need a data structure that treats all modalities (text, image) as properties of a single "Concept" or "Quiz Item".

## Decision
We will adopt a **Unified Item-Based Schema**.
Instead of separate lists, we will create a consolidated JSON file where each object represents a single service/concept and contains all its associated assets.

### Schema Design

```json
[
  {
    "id": "aws-ec2",
    "name": "EC2",
    "aliases": ["Amazon EC2", "Elastic Compute Cloud"],
    "category": "Compute",
    "description": {
      "en": "Scalable virtual machines in the cloud for general computing.",
      "ja": "汎用計算用にクラウドで拡張可能な仮想マシンを提供。"
    },
    "images": [
      {
        "type": "icon",
        "size": "64",
        "path": "img/.../Arch_Amazon-EC2_64.png"
      }
    ],
    "meta": {
      "free_tier": "750 hours/month..."
    }
  }
]
```

### Key Fields
*   **id**: Unique identifier (useful for tracking progress/stats).
*   **name**: The primary display name.
*   **description**: Localized descriptions.
*   **images**: An array of image objects. Using an array allows us to store different sizes (16, 32, 64) or types (icon, architecture diagram) and let the UI decide which to show.
*   **category**: Useful for filtering quizzes (e.g., "Only Compute services").

## Consequences
1.  **Data Migration**: We need to write a script to merge `aws.json` and `img.tree.json` into this new format.
    *   Matching strategy: Fuzzy match `service` name from `aws.json` against `name` in `img.tree.json`.
2.  **Frontend Update**: The quiz logic must be updated to handle this new structure.
    *   Instead of hardcoded `question = item.service`, the logic will be dynamic: `question = item[mode.questionType]`, `options = [otherItems[mode.answerType]]`.

## Alternatives Considered
*   **Keep Separate Files**: Keep `aws.json` and just add an `image_path` field.
    *   *Pros*: Simpler change.
    *   *Cons*: Less flexible if we want multiple images or advanced mappings later.
*   **Database**: SQLite or similar.
    *   *Pros*: Powerful querying.
    *   *Cons*: Overkill for a static file-based quiz app. JSON is portable and easy to edit.
