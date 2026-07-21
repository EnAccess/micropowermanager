import moment from "moment"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import AgentRepository from "@/repositories/AgentRepository.js"
import PersonRepository from "@/repositories/PersonRepository.js"
import { resources } from "@/resources.js"
import { EventBus } from "@/shared/eventbus.js"

export class AgentService {
  constructor() {
    this.repository = AgentRepository
    this.personRepository = PersonRepository
    this.list = []
    this.agent = {
      id: null,
      personId: null,
      miniGrid: null,
      miniGridId: null,
      password: null,
      nationality: null,
      deviceId: null,
      name: null,
      email: null,
      balance: null,
      commissionRevenue: null,
      phone: null,
      gender: null,
      birthday: null,
      commissionType: null,
      commissionTypeId: null,
    }
    this.paginator = new Paginator(resources.agents.list)
  }

  fromJson(data) {
    this.agent = {
      id: data.id,
      personId: data.person_id,
      miniGrid: data.mini_grid.name,
      miniGridId: data.mini_grid_id,
      deviceId: data.device_id,
      name: data.person.name,
      surname: data.person.surname,
      email: data.email,
      balance: data.balance,
      gender: data.person.gender,
      phone: data.person.addresses[0].phone,
      birthday: data.person.birth_date,
      commissionType: data.commission.name,
      commissionRevenue: data.commission_revenue,
      commissionTypeId: data.commission.id,
    }
    return this.agent
  }
  agentFromJson(data) {
    return {
      id: data.id,
      personId: data.person_id,
      miniGrid: data.mini_grid?.name,
      deviceId: data.device_id,
      name: data.person?.name,
      email: data.email,
      balance: data.balance,
      person: data.person, // Include the person object for name/surname
    }
  }
  updateList(data) {
    this.list = data.map(this.agentFromJson)
  }

  search(term) {
    this.paginator = new Paginator(resources.agents.search)
    EventBus.$emit("loadPage", this.paginator, { term: term })
  }

  showAll() {
    this.paginator = new Paginator(resources.agents.list)
    EventBus.$emit("loadPage", this.paginator)
  }

  async createAgent() {
    try {
      let agentPM = {
        name: this.agent.name,
        surname: this.agent.surname,
        is_customer: 0,
        nationality: this.agent.nationality,
        city_id: this.agent.miniGridId,
        email: this.agent.email,
        phone: this.agent.phone,
        is_primary: 1,
        agent_commission_id: this.agent.commissionTypeId,
        password: this.agent.password,
        birth_date: moment(this.agent.birthday).format("YYYY-MM-DD HH:mm:ss"),
        gender: this.agent.gender,
      }
      let response = await this.repository.create(agentPM)
      if (response.status === 201) {
        this.resetAgent()
        EventBus.$emit("agentAdded")
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateAgent(agent) {
    try {
      const payload = {
        id: agent.id,
        name: agent.name,
        surname: agent.surname,
        gender: agent.gender,
        birthday: agent.birthday
          ? moment(agent.birthday).format("YYYY-MM-DD")
          : null,
        phone: agent.phone,
        commissionTypeId: agent.commissionTypeId,
        miniGridId: agent.miniGridId,
      }
      let response = await this.repository.update(payload)
      if (response.status === 200) {
        this.agent = this.fromJson(response.data.data)
        return this.agent
      } else {
        new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async changePassword(agentId, password, passwordConfirmation) {
    try {
      const response = await this.repository.changePassword(agentId, {
        password,
        password_confirmation: passwordConfirmation,
      })
      if (response.status === 200) {
        return true
      }
      return new ErrorHandler(response.error, "http", response.status)
    } catch (e) {
      const errorMessage =
        e.response?.data?.message || "Failed to update password"
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }

  async getAgent(agentId) {
    try {
      let response = await this.repository.detail(agentId)
      if (response.status === 200 || response.status === 201) {
        return this.fromJson(response.data.data)
      } else {
        new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deleteAgent(agent) {
    try {
      let response = await this.repository.delete(agent.id)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        new ErrorHandler(response.error, "http", response.status)
      }
      return response
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  resetAgent() {
    this.agent = {
      id: null,
      personId: null,
      miniGrid: null,
      miniGridId: null,
      password: null,
      nationality: null,
      deviceId: null,
      name: null,
      email: null,
      balance: null,
      phone: null,
    }
  }
}
