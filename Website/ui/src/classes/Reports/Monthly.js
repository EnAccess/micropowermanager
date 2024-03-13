import { Paginator } from '../paginator'
import { resources } from '@/resources'
import { Report } from './Report'

export class Monthly {
    constructor() {
        this.list = []
        this.paginator = new Paginator(resources.reports.monthly.list)
    }

    async updateList(data) {
        this.list = []
        for (let r in data) {
            this.list.push(new Report().fromJson(data[r]))
        }
    }
}
