import { fs } from 'file-system'

export const FileService = {
    async configureDevice (companyId, miniGridId, timeZone, region) {
        //TODO: prepare folder structure and config files
    },

    async replaceFile(source, destination) {
        return new Promise((resolve, reject) => {
            fs.copyFile(source, destination, (err) => {
                if (err) {
                    console.log('Error Found:', err)
                    reject(err)
                } else {
                    console.log('File copied successfully!')
                    resolve(true)
                }
            })
        })

    }
}