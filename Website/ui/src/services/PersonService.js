import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import moment from 'moment'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'

export class PersonService {
    constructor() {
        this.repository = new Repository.get('person')
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
        }
        this.fullName = null
    }

    async createPerson(personData) {
        try {
            const params = convertObjectKeysToSnakeCase(personData)
            const { data, status, error } = await this.repository.create(params)
            if (status !== 200 && status !== 201)
                return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getPerson(personId) {
        try {
            const { data, status, error } = await this.repository.get(personId)
            if (status !== 200) return new ErrorHandler(error, 'http', status)
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
                        : 'No data available',
                gender: personData.sex,
                addresses: personData.addresses,
                devices: personData.devices,
            }

            return this.person
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async updatePerson(personData) {
        try {
            const person = convertObjectKeysToSnakeCase(personData)
            const { data, status, error } = await this.repository.update(person)
            if (status !== 200 && status !== 201)
                return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async deletePerson(personId) {
        try {
            const { data, status, error } =
                await this.repository.delete(personId)
            if (status !== 200 && status !== 201)
                return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async searchPerson(params) {
        try {
            let response = await this.repository.search(params)
            if (response.status === 200) {
                return response
            }
        } catch (e) {
            let erorMessage = e.response.data.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }

    getFullName() {
        this.fullName = this.person.name + ' ' + this.person.surname
        return this.fullName
    }

    getId() {
        return this.person.id
    }

    isoYear(date) {
        return moment(date).format('YYYY-MM-DD')
    }

    updateName(fullName) {
        let x = fullName.split(' ')
        if (x.length < 2) {
            return {
                success: false,
            }
        }
        this.person.surname = x.splice(-1)
        this.person.name = x.join(' ')
    }
}
