export const token = {
  methods: {
    formatToken(token) {
      // Ensure token is a string
      const tokenStr = String(token)
      // Format in the desired pattern
      // return tokenStr.match(/.{1,4}/g).join('-'); // For "1234-1234-1234"
      return tokenStr.match(/.{1,3}/g).join(" ") // For "123 412 341 234"
    },
    unitLabel(unit) {
      const labels = {
        currency: this.$tc("words.currency"),
        days: this.$tc("words.days"),
        kWh: this.$tc("words.kwh"),
      }
      return labels[unit] || unit
    },
  },
}
