import moment from 'moment'
export const timing = {
    methods: {
        timeForHuman(date) {
            return moment.utc(date, 'YYYY-MM-DD HH:mm:ss').fromNow()
        },
        //calculates the difference of the given two dates and gives a human understandable date back
        timeDiffForHuman(_startDate, _endDate) {
            let startDate = moment(_startDate, 'YYYY-MM-DD HH:mm:ss')
            let endDate = moment(_endDate, 'YYYY-MM-DD HH:mm:ss')

            return endDate.diff(startDate, 'seconds')
        }
    }
}
