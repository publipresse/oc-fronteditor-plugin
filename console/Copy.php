<?php namespace Publipresse\FrontEditor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use Cms\Classes\Theme;

use Log;
use Site;
use Config;
use ApplicationException;

/**
 * Copy Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class Copy extends Command
{
    /**
     * @var string signature for the console command.
     */

    protected $signature = 'fronteditor:copy {from?} {to?} {ignore?} {--o|overwrite}';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Copy content from one site to another';

    /**
     * handle executes the console command.
     */
    public function handle() {
        $from_id = $this->argument('from');
        $to_id = $this->argument('to');
        $ignoreDirs = $this->argument('ignore');
        $overwrite = $this->option('overwrite');
        
        if(!$from_id) $from_id = $this->ask('Website ID of the source of the copy');
        if(!$to_id) $to_id = $this->ask('Website ID of the destination of the copy');
        if(!$ignoreDirs) $ignoreDirs = explode(',', $this->ask('Do you want to ignore some folders (separate by a comma eg : folder1,folder2,folder3) can be anywhere in your folder structure'));
        if(!$overwrite) $overwrite = $this->confirm('Do you want to overwrite existing files ? [yes|no]', false);

        $errorMessages = self::copyFiles($from_id, $to_id, $overwrite, $ignoreDirs);

        if (!empty($errorMessages)) {
            $this->error('There were some errors during file copy, see event log for details.');
        } else {
            $this->info("All files copied successfully.");
        }
    }

    public static function copyFiles($from_id, $to_id, $overwrite = false, $ignoreDirs = []) {
        
        $from = Site::getSiteFromId($from_id);
        $to = Site::getSiteFromId($to_id);

        $from_theme = ($from->theme) ?: env('ACTIVE_THEME');
        $to_theme = ($to->theme) ?: env('ACTIVE_THEME');
    
        $from_code = $from->code;
        $to_code = $to->code;
    
        $errorMessages = [];
    
        if ($from_theme != $to_theme) {
            throw new ApplicationException("Source and target must share the same theme.");
        }
    
        if ($from_code == $to_code) {
            throw new ApplicationException("Source code and target code must be different.");
        }
    
        $source = themes_path($from_theme . '/content/' . $from_code);
        $target = themes_path($to_theme . '/content/' . $to_code);
    
        if (!File::isDirectory($source)) {
            throw new ApplicationException("Source folder doesn't exist.");
        }
    
        // Create target folder if not exists
        if (!File::isDirectory($target)) {
            File::makeDirectory($target, 0755, true);
        }
    
        // Récupère tous les fichiers du répertoire source
        $files = File::allFiles($source);

        foreach ($files as $file) {
            // Calcule le chemin relatif à partir du dossier source
            $relativePath = str_replace($source . '/', '', $file->getRealPath());
            $targetPath = $target . '/' . $relativePath;
        
            // Vérifie si le dossier relatif doit être ignoré
            $fileDir = dirname($relativePath);
        
            // Vérifie si le dossier ou ses sous-dossiers doivent être ignorés
            foreach ($ignoreDirs as $ignoreDir) {
                // Vérifie si le chemin du fichier contient le dossier à ignorer
                if (strpos($fileDir, $ignoreDir) !== false) {
                    continue 2; // Ignore ce fichier si son dossier ou un de ses sous-dossiers est dans le tableau
                }
            }
        
            // Crée le répertoire cible si nécessaire
            $targetDir = dirname($targetPath);
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }
        
            // If file exists and not overwrite, continue
            if (File::exists($targetPath) && !$overwrite) {
                continue;
            }
        
            // Copie le fichier
            try {
                File::copy($file->getRealPath(), $targetPath);
            } catch (\Exception $e) {
                // En cas d'erreur, ajoute le message au tableau
                $errorMessages[] = "Error copying file {$file->getFilename()}: " . $e->getMessage();
            }
        }
    
        // Afficher les messages d'erreur après la tentative de copie
        if (!empty($errorMessages)) {
            Log::error($errorMessages);
        }

        return $errorMessages;
    }
}
