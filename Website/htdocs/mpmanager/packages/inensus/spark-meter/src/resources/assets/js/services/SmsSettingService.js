import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class SmsSettingService {

    constructor () {
        this.repository = Repository.get('smsSetting')
        this.list = []
        this.smsSetting = {
            id: null,
            enabled: null,
            state: null,
            NotSendElderThanMins: null
        }
    }

    async updateSmsSettings (smsSettings) {
        try {
            let smsListPM = []
            for (let s in smsSettings) {
                let settingPm = {
                    id: smsSettings[s].settingType.id,
                    enabled: smsSettings[s].settingType.enabled,
                    state: smsSettings[s].settingType.state,
                    not_send_elder_than_mins: smsSettings[s].settingType.NotSendElderThanMins,
                }
                smsListPM.push(settingPm)
            }
            let response = await this.repository.update(smsListPM)
            if (response.status === 200) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}