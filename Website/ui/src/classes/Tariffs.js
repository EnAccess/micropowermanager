import { resources } from '@/resources'
import { AccessRate } from './AccessRate'
import { EventBus } from '@/shared/eventbus'

export class Tariff {
    constructor(
        id = 0,
        name = '',
        price = 0,
        currency = this.$store.getters['settings/getMainSettings'].currency,
        factor = 1,
        accessRate = null,
    ) {
        this.id = id
        this.name = name
        this.price = price
        this.currency = currency
        this.factor = factor
        this.accessRate = accessRate
    }

    setAccessRate(accessRate) {
        this.accessRate = accessRate
    }

    store() {
        axios
            .post(
                resources.tariff.create,
                {
                    name: this.name,
                    price: this.price,
                    currency: this.currency,
                    factor: this.factor,
                    access_rate_period: this.accessRate.period,
                    access_rate_amount: this.accessRate.amount,
                },
                {
                    headers: {
                        Authorization:
                            'Bearer ' + localStorage.getItem('token'),
                    },
                },
            )
            .then((response) => {
                this.id = response.data.data.id
                EventBus.$emit('tariffAdded', this)
            })
    }
}

export class Tariffs {
    constructor() {
        this.list = []
    }

    addTariff(tariff) {
        this.list.push(tariff)
    }

    getList() {
        axios
            .get(resources.tariff.list, {
                headers: {
                    Authorization: 'Bearer ' + localStorage.getItem('token'),
                },
            })
            .then((response) => {
                let data = response.data.data
                for (let i in data) {
                    let t = data[i]

                    let a = t.access_rate
                    let accessRate = null
                    if (a !== undefined) {
                        accessRate = new AccessRate(a.id, a.amount, a.period)
                    }

                    let tariff = new Tariff(
                        t.id,
                        t.name,
                        t.price,
                        t.currency,
                        t.factor,
                        accessRate,
                    )
                    this.list.push(tariff)
                }
            })
    }
}
