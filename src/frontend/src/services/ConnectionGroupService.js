import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"

import ConnectionGroupsRepository from "@/repositories/ConnectionGroupsRepository"

export class ConnectionGroupService {
  constructor() {
    this.repository = ConnectionGroupsRepository
    this.connectionGroups = []
    this.target = {
      newConnection: 0,
      totalRevenue: 0,
      connectedPower: 0,
      energyPerMonth: 0,
      averageRevenuePerMonth: 0,
    }
    this.connectionGroup = {
      id: null,
      name: null,
      target: this.target,
    }
    this.paginator = new Paginator(resources.connections.list)
    this.list = []
  }
  updateList(data) {
    this.connectionGroups = data.map((connection) => {
      return {
        id: connection.id,
        name: connection.name,
        updated_at: connection.updated_at,
        edit: false,
      }
    })
    return this.connectionGroups
  }
  async updateConnectionGroup(connectionGroup) {
    try {
      const { data, status, error } =
        await this.repository.update(connectionGroup)
      if (!status === 200 && !status === 201)
        return new ErrorHandler(error, "http", status)
      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async getConnectionGroups() {
    try {
      const { data, status, error } = await this.repository.list()
      if (!status === 200 && !status === 201)
        return new ErrorHandler(error, "http", status)
      this.connectionGroups = data.data
      this.list = data.data
      return this.connectionGroups
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async createConnectionGroup() {
    try {
      const params = {
        name: this.connectionGroup.name,
      }
      const { data, status, error } = await this.repository.create(params)
      if (!status === 200 && !status === 201)
        return new ErrorHandler(error, "http", status)
      this.resetConnectionGroup()
      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  resetConnectionGroup() {
    this.connectionGroup = {
      id: null,
      name: null,
      target: this.target,
    }
  }
}
