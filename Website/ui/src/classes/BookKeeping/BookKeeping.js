import { resources } from '@/resources'
import { Paginator } from '../paginator'

export class BookKeeping {
    constructor() {
        this.id = null
        this.date = null
        this.path = null
    }

    fromJson(data) {
        this.id = data.id
        this.date = data.date
        this.path = data.path
        return this
    }
}

export class BookKeepingList {
    constructor() {
        this.list = []
        this.paginator = new Paginator(resources.bookKeeping.list)
    }

    async updateList(data) {
        for (let bk in data) {
            this.list.push(new BookKeeping().fromJson(data[bk]))
        }
    }

    showAll() {}
}
