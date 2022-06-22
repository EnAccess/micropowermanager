import { resources } from '@/resources'
import moment from 'moment'
export class Person {

    constructor () {
        this.id = null
        this.title = null
        this.education = null
        this.birthDate = null
        this.name = null
        this.surname = null
        this.gender = null
        this.nationality = null
        this.city = null
    }

    initialize (personData) {
        this.id = personData.id
        this.title = personData.title
        this.education = personData.education
        this.birthDate = personData.birth_date
        this.name = personData.name
        this.surname = personData.surname
        this.nationality = personData.citizenship != null ? personData.citizenship.country_name : 'No data available'
        this.gender = personData.sex
        this.addresses = personData.addresses

        return this
    }

    updateName (fullName) {
        let x = fullName.split(' ')
        if (x.length < 2) {
            return {
                'success': false,

            }
        }
        this.surname = x.splice(-1)
        this.name = x.join(' ')
    }

    fromJson (data) {
        this.id = data.id
        this.title = data.title
        this.education = data.education
        this.birthDate = data.birth_date
        this.name = data.name
        this.surname = data.surname
        this.nationality = data.citizenship != null ? data.citizenship.country_name : 'No data available'
        this.gender = data.sex
        this.addresses = data.addresses
        this.lastUpdate = data.updated_at

        return this
    }

    toJson () {
        return {
            'title': this.title,
            'name': this.name,
            'surname': this.surname,
            'birth_date': this.birthDate,
            'sex': this.gender,
            'education': this.education,
        }
    }

    isoYear (date) {
        return moment(date).format('YYYY-MM-DD')

    }

    updatePerson () {
        this.updateName(this.name)
        if (this.birthDate !== null) {
            this.birthDate = this.isoYear(this.birthDate)
        }
        axios.put(resources.person.update + this.id, this.toJson())
    }

    getFullName () {
        return this.name + ' ' + this.surname
    }

    getId () {
        return this.id
    }

}
