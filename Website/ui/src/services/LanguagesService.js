import { ErrorHandler } from '@/Helpers/ErrorHander'
import LanguagesRepository from '@/repositories/LanguagesRepository'

export class LanguagesService {
    constructor() {
        this.repository = LanguagesRepository
        this.languagesList = []
    }

    reFormatData(data) {
        this.languagesList = []
        for (let i = 0; i < data.length; i++) {
            this.languagesList.push(data[i].split('.')[0])
        }
        return this.languagesList
    }

    async list() {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                return this.reFormatData(response.data.data)
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let erorMessage = e.response.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }
}
