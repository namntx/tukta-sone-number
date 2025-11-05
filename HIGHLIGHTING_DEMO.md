# Message Highlighting Feature Demo

## Feature Description
Parser bây giờ tự động highlight (tô đỏ) các token không nhận dạng được trong tin nhắn cược gốc.

## How It Works

Khi parse tin nhắn, nếu có token nào bị skip (không nhận dạng được), parser sẽ:
1. Track token đó trong events (kind: 'skip')
2. Tìm vị trí của token trong message gốc
3. Wrap token trong HTML với class `text-red-600 font-semibold`
4. Return trong field `highlighted_message`

## Response Structure

```json
{
  "is_valid": true,
  "multiple_bets": [...],
  "errors": [],
  "normalized": "tp  92 xyz 5n",
  "parsed_message": "tp  92 xyz 5n",
  "highlighted_message": "tp, 92 <span class=\"text-red-600 font-semibold\" title=\"Token không được nhận dạng\">xyz</span> 5n",
  "tokens": ["tp", "92", "xyz", "5n"],
  "debug": {
    "events": [
      ...
      {"kind": "skip", "token": "xyz"},
      ...
    ]
  }
}
```

## Example Use Cases

### Case 1: Single Unknown Token
**Input:** `tp, 92 xyz 5n`

**Output (highlighted_message):**
```html
tp, 92 <span class="text-red-600 font-semibold" title="Token không được nhận dạng">xyz</span> 5n
```

**Display:** tp, 92 <span style="color: red; font-weight: bold;">xyz</span> 5n

---

### Case 2: Multiple Unknown Tokens
**Input:** `tp, 92 abc def 5n`

**Output (highlighted_message):**
```html
tp, 92 <span class="text-red-600 font-semibold" title="Token không được nhận dạng">abc</span> <span class="text-red-600 font-semibold" title="Token không được nhận dạng">def</span> 5n
```

**Display:** tp, 92 <span style="color: red; font-weight: bold;">abc def</span> 5n

---

### Case 3: Valid Input (No Highlighting)
**Input:** `tp, 92 xc 5n`

**Output (highlighted_message):**
```html
tp, 92 xc 5n
```

**Display:** tp, 92 xc 5n (no red highlighting)

---

### Case 4: HTML Safety
Parser automatically escapes HTML to prevent XSS:

**Input:** `tp, 92 <script>alert("xss")</script> 5n`

**Output (highlighted_message):**
```html
tp, 92 &lt;span class=&quot;text-red-600...&quot;&gt;script&lt;/span&gt;...
```

All HTML special characters are properly escaped.

---

## Frontend Integration

### Using in Blade Templates

```blade
<!-- Display highlighted message with HTML rendering -->
<div class="message-preview">
    {!! $result['highlighted_message'] !!}
</div>
```

### Using in Vue/React

```javascript
// Safe to render as innerHTML (already escaped by parser)
<div dangerouslySetInnerHTML={{ __html: result.highlighted_message }} />
```

### CSS Classes Used

- `text-red-600` - Tailwind class for red color
- `font-semibold` - Tailwind class for semi-bold weight
- `title` attribute - Shows tooltip "Token không được nhận dạng" on hover

You can customize the appearance by modifying the CSS classes in `buildHighlightedMessage()` method.

---

## Benefits

1. **User Feedback** - Users immediately see which parts of their message weren't understood
2. **Error Prevention** - Helps users correct typos before submitting
3. **Learning Tool** - Shows users what syntax is valid vs invalid
4. **XSS Safe** - All HTML is properly escaped
5. **Preserves Original** - Original message structure and spacing preserved

---

## Technical Details

- **Encoding:** UTF-8 safe, handles Vietnamese characters
- **Performance:** O(n*m) where n = message length, m = skipped tokens count
- **Overlapping:** Automatically handles overlapping token positions
- **Case Insensitive:** Token matching is case-insensitive with accent normalization
