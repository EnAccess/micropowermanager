import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import { EventBus } from "@/shared/eventbus"

import ConnectionTypeRepository from "@/repositories/ConnectionTypeRepository"
import ConnectionGroupsRepository from "@/repositories/ConnectionGroupsRepository"
import SmsRepository from "@/repositories/SmsRepository"

export class SmsService {
  constructor() {
    this.repository = SmsRepository
    this.connectionGroupRepository = ConnectionGroupsRepository
    this.connectionTypeRepository = ConnectionTypeRepository

    this.sms = {
      id: null,
      number: null,
      date: null,
      message: null,
      total: 0,
      owner: null,
    }
    this.numberList = []
    this.list = []
    this.resultList = [] //used
    this.receiverList = [] //used
    this.paginator = new Paginator(resources.sms.list)
  }

  search(term) {
    this.paginator = new Paginator(resources.sms.search)
    EventBus.$emit("loadPage", this.paginator, { term: term })
  }

  showAll() {
    this.paginator = new Paginator(resources.sms.list)
    EventBus.$emit("loadPage", this.paginator)
  }

  updateList(smsList) {
    this.numberList = smsList.map((sms) => {
      let smsObj = {
        id: sms.id,
        number: sms.receiver,
        date: sms.created_at,
        message: sms.body,
        owner: "",
        total: 0,
      }
      if (sms.address !== null) {
        smsObj.owner = sms.address.owner
      }
      if ("total" in sms) {
        smsObj.total = sms.total
      }
      return smsObj
    })
    return this.numberList
  }

  searchSms(text) {
    if (text.length === 0) {
      return this.numberList
    }
    return this.numberList.filter((n) => {
      return (
        n.number.includes(text) ||
        (n.owner !== null &&
          n.owner !== undefined &&
          n.owner.name !== undefined &&
          n.owner.surname !== undefined) ||
        n.owner.name.toLowerCase().includes(text.toLowerCase()) ||
        n.owner.surname.toLowerCase().includes(text.toLowerCase())
      )
    })
  }

  addReceiver(receiverToAdd) {
    const searchForReeciver = this.receiverList.filter(function (receiver) {
      return (
        (receiverToAdd.stored && receiver.id === receiverToAdd.id) ||
        (!receiverToAdd.stored && receiver.phone === receiverToAdd.phone)
      )
    })

    if (searchForReeciver.length === 0) {
      this.receiverList.push(receiverToAdd)
    }
  }

  addConnectionGroupReceiver(receiverToAdd) {
    this.receiverList = [receiverToAdd]
  }

  removeReceiver(receiverToRemove) {
    this.receiverList = this.receiverList.filter(function (receiver) {
      return receiverToRemove.display !== receiver.display
    })
  }

  async getList(personId) {
    try {
      let response = await this.repository.list("list", personId)
      if (response.status === 200) {
        if (personId !== null) {
          return response.data.data
        }
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async getDetail(phone) {
    try {
      let response = await this.repository.detail(phone)
      if (response.status === 200) {
        this.list = response.data.data
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async sendMaintenanceSms(maintenanceData) {
    try {
      let sendSmsPM = {
        person_id: maintenanceData.assigned.person.id,
        message:
          maintenanceData.description +
          "\n Amount : " +
          maintenanceData.amount +
          "\n Due Date " +
          maintenanceData.dueDate,
        senderId: maintenanceData.id,
      }
      let response = await this.repository.send(sendSmsPM, "single")
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async sendToNumber(type, message, phone, senderId) {
    let sendSmsPM = {
      type: type,
      message: message,
      phone: phone,
      senderId: senderId,
    }
    try {
      let response = await this.repository.send(sendSmsPM, "single")
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async sendToPerson(message, personId, senderId) {
    let sendSmsPM = {
      message: message,
      person_id: personId,
      senderId: senderId,
    }
    try {
      let response = await this.repository.send(sendSmsPM, "single")
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async sendBulk(type, message, senderId, miniGrid) {
    let receivers
    if (type === "person") {
      receivers = this.receiverList
        .filter((receiver) => receiver.phone != null)
        .map(function (receiver) {
          return receiver.phone
        })
    } else if (type === "group" || type === "type") {
      receivers = this.receiverList
    } else if (type === "all") {
      receivers = []
    }

    let sendSmsPM = {
      type: type,
      miniGrid: miniGrid,
      receivers: receivers,
      message: message,
      senderId: senderId,
    }
    this.resetLists()
    try {
      const response = await this.repository.send(sendSmsPM, "bulk")
      if (response.status !== 200 || response.status !== 201) {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async connectionGroupList() {
    try {
      const { data, status } = await this.connectionGroupRepository.list()
      return status === 200
        ? this.fetchGroupsSearchResult(data.data)
        : new ErrorHandler(
            "Get connection groups ended with " + status,
            "http",
            status,
          )
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async connectionTypeList() {
    try {
      const { data, status } = await this.connectionTypeRepository.list()
      return status === 200
        ? this.fetchGroupsSearchResult(data.data)
        : new ErrorHandler(
            "Get connection groups ended with " + status,
            "http",
            status,
          )
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async searchPerson(term) {
    try {
      const { data, status } = await this.repository.search(term)
      return status === 200
        ? this.fetchSearchResult(data.data)
        : new ErrorHandler(
            "Sms resulted with status code " + status,
            "http",
            status,
          )
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  fetchSearchResult(searchedList) {
    this.resultList = searchedList.map(function (person) {
      return {
        id: person.id,
        phone: person.phone,
        display: person.display,
      }
    })
  }

  fetchGroupsSearchResult(searchedList) {
    this.resultList = searchedList.map(function (connectionGroups) {
      return {
        id: connectionGroups.id,
        display: connectionGroups.name,
      }
    })
  }

  resetLists() {
    this.resultList = []
    this.receiverList = []
  }
}
