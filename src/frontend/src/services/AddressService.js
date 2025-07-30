import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AddressRepository from "@/repositories/AddressRepository"

export class Address {
  fromJson(data) {
    let item = data
    this.id = item.id
    this.email = item.email
    this.phone = item.phone
    this.street = item.street !== null ? item.street : "-"
    this.city = item.city !== null ? item.city.name : "-"
    this.city_id = item.city_id
    this.primary = item.is_primary !== 0
    this.created_at = item.created_at
    return this
  }
}

export class Addresses {
  constructor(personId) {
    this.repository = AddressRepository
    this.list = []
    this.personId = personId
    this.paginator = new Paginator(
      resources.person.addresses + personId + "/addresses",
    )
  }

  appendList(data) {
    let address = new Address()
    address.fromJson(data)
    this.list.push(address)
    return this.list
  }

  updateList(data) {
    this.list = []
    for (let t in data) {
      let address = new Address()
      address.fromJson(data[t])
      this.list.push(address)
    }
  }

  async updateAddress(newAddress) {
    try {
      let response = await this.repository.update(newAddress, this.personId)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
  async newAddress(newAddress) {
    try {
      let response = await this.repository.create(newAddress, this.personId)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
}
