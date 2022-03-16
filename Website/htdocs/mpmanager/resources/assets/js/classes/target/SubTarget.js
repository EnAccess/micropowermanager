import {ConnectionsType} from '../connection/ConnectionsType'

export class SubTarget {

    constructor() {
        this.id = null
        this.targetId = null
        this.revenue = null
        this.newConnections = null
        this.revenue = null
    }


    fromJson(jsonData) {

        this.id = jsonData.id
        this.targetId = jsonData.target_id
        this.revenue = jsonData.revenue
        this.newConnections = jsonData.new_connections
        this.revenue = jsonData.revenue

        let connectionType = new ConnectionsType()
        this.connections = connectionType.fromJson(jsonData.connection_type)
        return this
    }

}
