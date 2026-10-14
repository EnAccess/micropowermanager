export const token = {
  methods: {
    async copyToken(value) {
      try {
        await navigator.clipboard.writeText(String(value))
        this.alertNotify("success", this.$tc("phrases.tokenCopied"))
      } catch (error) {
        this.alertNotify("error", this.$tc("phrases.tokenCopyFailed"))
      }
    },
    formatToken(token) {
      // Ensure token is a string
      const tokenStr = String(token)
      // Format in the desired pattern
      // return tokenStr.match(/.{1,4}/g).join('-'); // For "1234-1234-1234"
      return tokenStr.match(/.{1,3}/g).join(" ") // For "123 412 341 234"
    },
    tokenTypeLabel(tokenType) {
      const labels = {
        energy: this.$tc("words.energy"),
        time: this.$tc("words.time"),
        unlock: this.$tc("words.unlock"),
        reset: this.$tc("words.reset"),
      }
      return labels[tokenType] || tokenType
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
