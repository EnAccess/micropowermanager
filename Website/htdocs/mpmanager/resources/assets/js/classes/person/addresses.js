import {Paginator} from '../paginator'
import {resources} from '../../resources'

export class Address {


    fromJson(data) {
        let item = data
        this.id = item.id
        this.email = item.email
        this.phone = item.phone
        this.street = item.street !== null ? item.street : '-'
        this.city = item.city !== null ? item.city.name : '-'
        this.city_id = item.city_id
        this.primary = item.is_primary !== 0
        this.created_at = item.created_at
        return this
    }
}

export class Addresses {
    constructor(personId) {
        this.list = []
        this.paginator = new Paginator(resources.person.addresses + personId + '/addresses')
    }

    appendList(data) {
        let address = new Address()
        address.fromJson(data)
        this.list.push(address)
    }

    updateList(data) {
        this.list = []
        for (let t in data) {
            let address = new Address()
            address.fromJson(data[t])
            this.list.push(address)

        }
    }
}
