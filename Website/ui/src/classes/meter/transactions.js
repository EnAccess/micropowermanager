import {Paginator} from '../paginator'
import {resources} from '@/resources'

export class Transaction {

}

export class Transactions {
    constructor(meterId) {
        this.tokens = []
        this.paginator = new Paginator(resources.meters.transactions + meterId + '/transactions')
    }

    updateList(data) {
        this.tokens = []
        for (let t in data) {
            this.tokens.push(data[t])
        }
    }


}
