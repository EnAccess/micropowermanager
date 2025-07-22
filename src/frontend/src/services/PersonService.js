import { ErrorHandler } from "@/Helpers/ErrorHandler"
import moment from "moment"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils"
import { resources } from "@/resources"

import { Paginator } from "@/Helpers/Paginator"
import { EventBus } from "@/shared/eventbus"

import PersonRepository from "@/repositories/PersonRepository"

export class Person {
  constructor() {
    this.id = null
    this.title = null
    this.education = null
    this.birthDate = null
    this.name = null
    this.surname = null
    this.gender = null
    this.nationality = null
    this.city = null
    this.devices = []
    this.is_active = null
  }

  initialize(personData) {
    this.id = personData.id
    this.title = personData.title
    this.education = personData.education
    this.birthDate = personData.birth_date
    this.name = personData.name
    this.surname = personData.surname
    this.nationality =
      personData.citizenship != null
        ? personData.citizenship.country_name
        : "No data available"
    this.gender = personData.sex
    this.addresses = personData.addresses
    this.devices = personData.devices
    this.is_active = personData.is_active

    return this
  }

  updateName(fullName) {
    let x = fullName.split(" ")
    if (x.length < 2) {
      return {
        success: false,
      }
    }
    this.surname = x.splice(-1)
    this.name = x.join(" ")
  }

  fromJson(data) {
    this.id = data.id
    this.title = data.title
    this.education = data.education
    this.birthDate = data.birth_date
    this.name = data.name
    this.surname = data.surname
    this.nationality =
      data.citizenship != null
        ? data.citizenship.country_name
        : "No data available"
    this.gender = data.sex
    this.addresses = data.addresses
    this.lastUpdate = data.updated_at
    this.devices = data.devices
    this.is_active = data.is_active

    return this
  }

  toJson() {
    return {
      title: this.title,
      name: this.name,
      surname: this.surname,
      birth_date: this.birthDate,
      sex: this.gender,
      education: this.education,
    }
  }

  isoYear(date) {
    return moment(date).format("YYYY-MM-DD")
  }

  updatePerson() {
    this.updateName(this.name)
    if (this.birthDate !== null) {
      this.birthDate = this.isoYear(this.birthDate)
    }
    axios.put(resources.person.update + this.id, this.toJson())
  }

  getFullName() {
    return this.name + " " + this.surname
  }

  getId() {
    return this.id
  }
}

export class People {
  constructor() {
    this.list = []
    this.paginator = new Paginator(resources.person.list)
  }

  search(term) {
    this.paginator = new Paginator(resources.person.search)
    EventBus.$emit("loadPage", this.paginator, { term: term })
  }

  showAll() {
    this.paginator = new Paginator(resources.person.list)
    EventBus.$emit("loadPage", this.paginator)
  }

  async updateList(data) {
    this.list = []

    for (let m in data) {
      let person = new Person().fromJson(data[m])
      this.list.push(person)
    }
  }
}

export class PersonService {
  constructor() {
    this.repository = PersonRepository
    this.person = {
      id: null,
      title: null,
      education: null,
      birthDate: null,
      name: null,
      surname: null,
      gender: null,
      nationality: null,
      city: null,
      devices: [],
      addresses: [],
      address: {
        street: null,
        cityId: null,
        email: null,
        phone: null,
      },
      is_active: null,
    }
    this.fullName = null
  }

  async createPerson(personData) {
    try {
      const params = convertObjectKeysToSnakeCase(personData)
      const { data, status, error } = await this.repository.create(params)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getPerson(personId) {
    try {
      const { data, status, error } = await this.repository.get(personId)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      const personData = data.data
      this.person = {
        id: personData.id,
        title: personData.title,
        education: personData.education,
        birthDate: personData.birth_date,
        name: personData.name,
        surname: personData.surname,
        nationality:
          personData.citizenship != null
            ? personData.citizenship.country_name
            : "No data available",
        gender: personData.sex,
        addresses: personData.addresses,
        devices: personData.devices,
        is_active: personData.is_active,
      }

      return this.person
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updatePerson(personData) {
    try {
      const person = convertObjectKeysToSnakeCase(personData)
      const { data, status, error } = await this.repository.update(person)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deletePerson(personId) {
    try {
      const { data, status, error } = await this.repository.delete(personId)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async searchPerson(params) {
    try {
      let response = await this.repository.search(params)
      if (response.status === 200) {
        return response
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }

  getFullName() {
    this.fullName = this.person.name + " " + this.person.surname
    return this.fullName
  }

  getId() {
    return this.person.id
  }

  isoYear(date) {
    return moment(date).format("YYYY-MM-DD")
  }

  updateName(fullName) {
    let x = fullName.split(" ")
    if (x.length < 2) {
      return {
        success: false,
      }
    }
    this.person.surname = x.splice(-1)
    this.person.name = x.join(" ")
  }
}
