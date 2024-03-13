import { resources } from '@/resources'
import { ConnectionsType } from './ConnectionsType'
import { Paginator } from '../paginator'
import Client, { baseUrl } from '../../repositories/Client/AxiosClient'

export class ConnectionTypes {
    constructor() {
        this.list = []
        this.connection = new ConnectionsType()
        this.paginator = new Paginator(resources.connections.list)
    }

    reSetConnection() {
        this.connection = new ConnectionsType()
    }

    getConnectionTypes() {
        Client.get(baseUrl + resources.connections.list + '?paginate=1').then(
            (response) => {
                this.fromJson(response.data.data)
                return this.list
            },
        )
    }

    getSubConnectionTypes() {
        Client.get(
            baseUrl + resources.connections.sublist + '?paginate=1',
        ).then((response) => {
            this.fromJson(response.data.data)
            return this.list
        })
    }

    fromJson(jsonData) {
        for (let c in jsonData) {
            this.reSetConnection()
            this.list.push(this.connection.fromJson(jsonData[c]))
        }
    }

    async updateList(data) {
        this.list = []

        for (let c in data) {
            let connectionType = new ConnectionsType()
            this.list.push(connectionType.fromJson(data[c]))
        }
    }
}
