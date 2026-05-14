const tokenPatterns = {
  author: /author:("[^"]+"|\S+)/gi,
  journal: /journal:("[^"]+"|\S+)/gi,
  year: /year:("[^"]+"|\S+)/gi,
}

function cleanToken(raw) {
  if (!raw) {
    return ''
  }
  return raw.replace(/^"|"$/g, '').trim()
}

export function parseAdvancedQuery(input) {
  const source = (input || '').trim()
  let working = source

  const extracted = {
    keyword: '',
    author: '',
    journal: '',
    year: '',
  }

  Object.entries(tokenPatterns).forEach(([key, pattern]) => {
    const matches = [...working.matchAll(pattern)]
    if (matches.length) {
      const value = matches[matches.length - 1][1]
      extracted[key] = cleanToken(value)
      working = working.replace(pattern, ' ')
    }
  })

  extracted.keyword = working.replace(/\s+/g, ' ').trim()

  if (extracted.year && !/^\d{4}$/.test(extracted.year)) {
    extracted.year = ''
  }

  return extracted
}
