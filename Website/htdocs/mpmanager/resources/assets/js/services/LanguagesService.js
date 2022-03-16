import RepositoryFactory from '../repositories/RepositoryFactory'
import {ErrorHandler} from '../Helpers/ErrorHander'

export  class LanguagesService{
    constructor () {
        this.repository = RepositoryFactory.get('languagesList')
        this.languagesList = []
    }

    reFormatData(data){
        this.languagesList = []
        for (let i=0; i<data.length; i++){
            this.languagesList.push(data[i].split('.')[0])
        }
        return this.languagesList
    }

    async list(){
        try {
            let response = await this.repository.list()
            if(response.status === 200 ){
                return this.reFormatData(response.data.data)
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }

        }catch (e) {
            let erorMessage = e.response.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }


}
