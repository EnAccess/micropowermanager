import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { Paginator } from '@/classes/paginator'
import { resources } from '@/resources'

import Client, { baseUrl } from '../../repositories/Client/AxiosClient'

export class ConnectionsType {
    constructor() {
        this.id = null
        this.name = null
        this.target = {
            newConnection: 0,
            totalRevenue: 0,
            connectedPower: 0,
            energyPerMonth: 0,
            averageRevenuePerMonth: 0,
        }
    }

    fromJson(jsonData) {
        if (jsonData) {
            this.id = jsonData.id
            this.name = jsonData.name
        }

        return this
    }

    store() {
        return Client.post(baseUrl + resources.connections.store, {
            name: this.name,
        })
    }
}

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

export class ConnectionTypeService {
    constructor() {
        this.repository = RepositoryFactory.get('connectionTypes')
        this.connectionTypes = []
        this.target = {
            newConnection: 0,
            totalRevenue: 0,
            connectedPower: 0,
            energyPerMonth: 0,
            averageRevenuePerMonth: 0,
        }
        this.connectionType = {
            id: null,
            name: null,
            target: this.target,
        }
        this.paginator = new Paginator(resources.connections.store)
        this.list = []
    }

    updateList(data) {
        this.connectionTypes = data.map((connection) => {
            return {
                id: connection.id,
                name: connection.name,
                updated_at: connection.updated_at,
                edit: false,
            }
        })
        return this.connectionTypes
    }

    async updateConnectionType(connectionType) {
        try {
            const { data, status, error } =
                await this.repository.update(connectionType)
            if (!status === 200 && !status === 201)
                return new ErrorHandler(error, 'http', status)
            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getConnectionTypes() {
        try {
            const { data, status, error } = await this.repository.list()
            if (!status === 200) return new ErrorHandler(error, 'http', status)
            this.connectionTypes = data.data
            this.list = data.data
            return this.connectionTypes
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getConnectionTypeDetail(connectionTypeId) {
        try {
            const { data, status, error } =
                await this.repository.show(connectionTypeId)
            if (!status === 200) return new ErrorHandler(error, 'http', status)
            this.connectionType = data.data
            return this.connectionType
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async createConnectionType() {
        try {
            const params = {
                name: this.connectionType.name,
            }
            const { data, status, error } = await this.repository.create(params)
            if (!status === 200 && status === 201)
                return new ErrorHandler(error, 'http', status)
            this.resetConnectionType()
            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    resetConnectionType() {
        this.connectionType = {
            id: null,
            name: null,
            target: this.target,
        }
    }
}
