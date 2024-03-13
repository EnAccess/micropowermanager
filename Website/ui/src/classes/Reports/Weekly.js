import { Paginator } from '../paginator'
import { resources } from '@/resources'
import { Report } from './Report'

export class Weekly {
    constructor() {
        this.list = []
        this.paginator = new Paginator(resources.reports.weekly.list)
    }

    async updateList(data) {
        for (let r in data) {
            this.list.push(new Report().fromJson(data[r]))
        }
    }
}
