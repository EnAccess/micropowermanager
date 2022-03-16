import { Paginator } from './paginator'
import { resources } from '../resources'
import { EventBus } from '../shared/eventbus'
import { Person } from './person'

export class People {
    constructor () {
        this.list = []
        this.paginator = new Paginator(resources.person.list)
    }

    search (term) {
        this.paginator = new Paginator(resources.person.search)
        EventBus.$emit('loadPage', this.paginator, {'term': term})
    }

    showAll () {
        this.paginator = new Paginator(resources.person.list)
        EventBus.$emit('loadPage', this.paginator)
    }

    async updateList (data) {
        this.list = []

        for (let m in data) {
            let person = new Person().fromJson(data[m])
            person.meters = data[m].meters
            //person.fromJson(data[m])
            this.list.push(person)
        }
    }

}
