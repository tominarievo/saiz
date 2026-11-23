#!/usr/bin/env python3

import json
import os
import sys

MAX_CHARS = 10000
MAX_BYTES = 45000
DEFAULT_REPLACEMENT = '[氏名]'


def iter_chunks(text):
    lines = text.splitlines(keepends=True)
    buffer = []
    byte_count = 0
    char_count = 0

    for line in lines:
        while len(line.encode('utf-8')) > MAX_BYTES:
            cut = MAX_CHARS

            while cut > 0 and len(line[:cut].encode('utf-8')) > MAX_BYTES:
                cut -= 1000

            if cut <= 0:
                cut = 1

            if buffer:
                yield ''.join(buffer)
                buffer = []
                byte_count = 0
                char_count = 0

            yield line[:cut]
            line = line[cut:]

        line_bytes = len(line.encode('utf-8'))
        line_chars = len(line)

        if buffer and (byte_count + line_bytes > MAX_BYTES or char_count + line_chars > MAX_CHARS):
            yield ''.join(buffer)
            buffer = []
            byte_count = 0
            char_count = 0

        buffer.append(line)
        byte_count += line_bytes
        char_count += line_chars

    if buffer:
        yield ''.join(buffer)


def redact_chunk(nlp, text, labels, replacement):
    doc = nlp(text)
    entities = [entity for entity in doc.ents if entity.label_.lower() in labels]

    if not entities:
        return text, 0

    pieces = []
    last = 0
    count = 0

    for entity in entities:
        if entity.start_char < last:
            continue

        pieces.append(text[last:entity.start_char])
        pieces.append(replacement)
        last = entity.end_char
        count += 1

    pieces.append(text[last:])

    return ''.join(pieces), count


def main():
    try:
        payload = json.loads(sys.stdin.buffer.read().decode('utf-8'))
    except Exception as error:
        sys.stdout.write(json.dumps({'error': 'invalid input: ' + str(error)}, ensure_ascii=False))
        return 1

    text = payload.get('text')

    if not isinstance(text, str):
        sys.stdout.write(json.dumps({'error': 'text is required'}, ensure_ascii=False))
        return 1

    if text == '':
        sys.stdout.write(json.dumps({'text': '', 'counts': {'person': 0}}, ensure_ascii=False))
        return 0

    labels = {
        label.strip().lower()
        for label in os.environ.get('PII_NER_LABELS', 'person').split(',')
        if label.strip()
    }
    replacement = os.environ.get('PII_NER_REPLACEMENT', DEFAULT_REPLACEMENT)

    try:
        import spacy

        nlp = spacy.load('ja_ginza', disable=['parser'])
        nlp.max_length = max(nlp.max_length, MAX_CHARS + 1000)
    except Exception as error:
        sys.stdout.write(json.dumps({'error': 'ginza unavailable: ' + str(error)}, ensure_ascii=False))
        return 1

    redacted_chunks = []
    total = 0

    try:
        for chunk in iter_chunks(text):
            redacted, count = redact_chunk(nlp, chunk, labels, replacement)
            redacted_chunks.append(redacted)
            total += count
    except Exception as error:
        sys.stdout.write(json.dumps({'error': 'redaction failed: ' + str(error)}, ensure_ascii=False))
        return 1

    sys.stdout.write(json.dumps({
        'text': ''.join(redacted_chunks),
        'counts': {'person': total},
    }, ensure_ascii=False))

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
