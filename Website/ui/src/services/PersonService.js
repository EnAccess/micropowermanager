import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import moment from 'moment'

export class PersonService {
    constructor () {
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
            devices: []
        }
        this.fullName = null
    }

    async getPerson (personId) {
        try {

            const response = await this.repository.get(personId)
            if (response.status === 200 || response.status === 201) {
                const { data : personData} = response.data
                this.person = {
                    id: personData.id,
                    title: personData.title,
                    education: personData.education,
                    birthDate: personData.birth_date,
                    name: personData.name,
                    surname: personData.surname,
                    nationality: personData.citizenship != null ? personData.citizenship.country_name : 'No data available',
                    gender: personData.sex,
                    addresses: personData.addresses,
                    devices: personData.devices
                }
                return this.person
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')

        }

    }

    getFullName () {
        this.fullName = this.person.name + ' ' + this.person.surname
        return this.fullName
    }

    getId () {
        return this.person.id
    }

    async updatePerson () {
        try {
            this.updateName(this.person.name)
            if (this.person.birthDate !== null) {
                this.person.birthDate = this.isoYear(this.person.birthDate)
            }
            const personPM = {
                'id': this.person.id,
                'title': this.person.title,
                'education': this.person.education,
                'birth_date': this.person.birthDate,
                'name': this.person.name,
                'surname': this.person.surname,
                'sex': this.person.gender
            }
            let response = await this.repository.update(personPM)
            if (response.status === 200 || response.status === 201) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')

        }
    }

    isoYear (date) {
        return moment(date).format('YYYY-MM-DD')

    }

    updateName (fullName) {
        let x = fullName.split(' ')
        if (x.length < 2) {
            return {
                'success': false,

            }
        }
        this.person.surname = x.splice(-1)
        this.person.name = x.join(' ')
    }

    async deletePerson(personId){
        try {
            let response = await this.repository.delete(personId)
            if(response.status === 200 || response.status === 201){
                return response
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            let erorMessage = e.response.data.data.message
            return new ErrorHandler(erorMessage, 'http')
        }

    }
    async searchPerson(params){

        try {
            let response = await this.repository.search(params)
            if(response.status === 200){
                return response
            }
        }catch (e) {
            let erorMessage = e.response.data.data.message
            return new ErrorHandler(erorMessage, 'http')
        }

    }
}
