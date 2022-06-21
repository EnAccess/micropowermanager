import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class SyncSettingService {

    constructor() {
        this.repository = Repository.get('syncSetting')
        this.list = []
        this.syncSetting = {
            id: null,
            actionName: null,
            syncInMins: null,
            timeValueInt: null,
            timeValueStr: null,
            maxAttempts: null,
        }
    }

    async updateSyncSettings(syncSettings) {
        try {
            let syncListPM = []
            for (let s in syncSettings) {
                let settingPm = {
                    id: syncSettings[s].settingType.id,
                    action_name: syncSettings[s].settingType.actionName,
                    sync_in_value_str: syncSettings[s].settingType.syncInValueStr,
                    sync_in_value_num: syncSettings[s].settingType.syncInValueNum,
                    max_attempts: syncSettings[s].settingType.maxAttempts

                }
                syncListPM.push(settingPm)
            }
            let response = await this.repository.update(syncListPM)
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