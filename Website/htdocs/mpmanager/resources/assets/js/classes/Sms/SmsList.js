import { Paginator } from '../paginator'
import { resources } from '../../resources'
import { EventBus } from '../../shared/eventbus'

export class Sms {
    constructor () {
        this.id = null
        this.number = null
        this.date = null
        this.message = null
        this.total = 0
        this.owner = null
    }

    fromJson (data) {
        this.id = data.id
        this.number = data.receiver
        this.date = data.created_at
        this.message = data.body
        if (data.address !== null) {
            this.owner = data.address.owner
        }
        if ('total' in data) {
            this.total = data.total
        }
        return this
    }
}

export class Smses {
    constructor () {
        this.numberList = []
        this.list = []
        this.paginator = new Paginator(resources.sms.list)
    }

    getList () {
        return axios.get(resources.sms.list)
            .then((response) => {
                this.updateList(response.data.data)
            })
    }

    async updateList (data) {
        this.numberList = []
        for (let s in data) {
            let sms = new Sms()
            this.numberList.push(
                sms.fromJson(data[s])
            )
        }
    }

    getDetail (phone) {
        axios.get(resources.sms.byPhone + '/' + phone).then((response) => {
            this.list = response.data.data
        })
    }

    search (term) {
        this.paginator = new Paginator(resources.sms.search)
        EventBus.$emit('loadPage', this.paginator, {'term': term})
    }

    showAll () {
        this.paginator = new Paginator(resources.sms.list)
        EventBus.$emit('loadPage', this.paginator)
    }

}
