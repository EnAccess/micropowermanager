import moment from "moment-timezone"

export const timing = {
  methods: {
    timeForTimeZone(date) {
      const timezone = moment.tz.guess()
      return moment
        .utc(date, "YYYY-MM-DD HH:mm")
        .tz(timezone)
        .format("YYYY-MM-DD HH:mm")
    },
    timeForHuman(date) {
      const timezone = moment.tz.guess()
      return moment.utc(date, "YYYY-MM-DD HH:mm").tz(timezone).fromNow()
    },
    //calculates the difference of the given two dates and gives a human understandable date back
    timeDiffForHuman(_startDate, _endDate) {
      const timezone = moment.tz.guess()
      const startDate = moment(_startDate, "YYYY-MM-DD HH:mm:ss").tz(timezone)
      const endDate = moment(_endDate, "YYYY-MM-DD HH:mm:ss").tz(timezone)

      return endDate.diff(startDate, "seconds")
    },
  },
}
