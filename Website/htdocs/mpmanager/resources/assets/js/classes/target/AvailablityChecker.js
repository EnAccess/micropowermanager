import {resources} from '../../resources'
import moment from 'moment'

export class AvailablityChecker {

    constructor() {
        this.takenSlots = []
    }


    // returns the blocked slots
    checkSlots(targetDate) {
        return axios.post(resources.target.available_slots,
            {'date': targetDate}
        ).then((response) => {
            for (let t in response.data.data) {
                let takenSlot = response.data.data[t]

                this.takenSlots.push(moment(takenSlot.target_date).toDate())
            }
        })

    }
}
