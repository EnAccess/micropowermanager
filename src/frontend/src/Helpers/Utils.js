export const convertObjectKeysToCamelCase = (obj) => {
  const result = {}
  for (const [key, value] of Object.entries(obj)) {
    const camelKey = snakeToCamel(key)

    if (value !== null && typeof value === "object" && !Array.isArray(value)) {
      result[camelKey] = convertObjectKeysToCamelCase(value)
    } else if (Array.isArray(value)) {
      result[camelKey] = value?.map((item) =>
        typeof item === "object" && item !== null
          ? convertObjectKeysToCamelCase(item)
          : item,
      )
    } else {
      result[camelKey] = value
    }
  }
  return result
}
const snakeToCamel = (str) => {
  return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase())
}
export const convertObjectKeysToSnakeCase = (obj) => {
  const result = {}
  for (const [key, value] of Object.entries(obj)) {
    const snakeKey = camelToSnake(key)

    if (
      value !== null &&
      typeof value === "object" &&
      value instanceof Date === false &&
      !Array.isArray(value)
    ) {
      result[snakeKey] = convertObjectKeysToSnakeCase(value)
    } else if (Array.isArray(value)) {
      result[snakeKey] = value?.map((item) =>
        typeof item === "object" && item !== null
          ? convertObjectKeysToSnakeCase(item)
          : item,
      )
    } else {
      result[snakeKey] = value
    }
  }
  return result
}

const camelToSnake = (str) => {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`)
}
