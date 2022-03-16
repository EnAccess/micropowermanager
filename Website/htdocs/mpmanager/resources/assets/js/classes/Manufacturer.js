import {resources} from '../resources'

export class Manufacturer {
    constructor(id = 0, name = '') {
        this.id = id
        this.manufacturerName = name
    }


    fromJson(jsonData) {
        this.id = jsonData.id
        this.manufacturerName = jsonData.name
        this.webSite = jsonData.website
        return this
    }

}


export class Manufacturers {


    constructor() {
        this.list = []
    }

    async getList() {
        return await axios.get(resources.manufacturer.list).then(
            response => {
                let data = response.data.data
                for (let m in data) {
                    let manufacturer = new Manufacturer()
                    this.list.push(manufacturer.fromJson(data[m]))
                }
                return this.list
            }
        )
    }

    findById() {
        this.list.find(function (m) {
            return m.id == 2
        })

        return new Manufacturer(1, 'Hebele')
    }

}
