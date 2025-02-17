import pandas as pd

# Charger les fichiers XLSX
file_paths = {
    "TAL": "/mnt/data/TAL.xlsx",
    "TLL1": "/mnt/data/TLL1.xlsx",
    "TLL2": "/mnt/data/TLL2.xlsx"
}

# Liste des colonnes attendues dans le fichier de sortie
columns = ["matricule", "nom", "prenom", "date_naiss", "lieu_naiss", "sexe",
           "adresse", "tel", "pere", "mere"]

# Initialiser une liste pour stocker les données combinées
data_list = []

# Lire les fichiers et extraire les données
for key, path in file_paths.items():
    xls = pd.ExcelFile(path)
    for sheet_name in xls.sheet_names:
        df = pd.read_excel(xls, sheet_name=sheet_name)
        
        # Nettoyer les noms de colonnes pour éviter les erreurs
        df.columns = df.columns.str.strip().str.lower()
        
        # Sélectionner les colonnes qui correspondent (supposons qu'elles existent dans les fichiers)
        df = df[columns] if all(col in df.columns for col in columns) else None

        # Ajouter au jeu de données si les colonnes sont correctes
        if df is not None:
            data_list.append(df)

# Concaténer tous les fichiers en un seul DataFrame
if data_list:
    final_df = pd.concat(data_list, ignore_index=True)

    # Sauvegarder le fichier final au format CSV
    final_csv_path = "/mnt/data/converted_data.csv"
    final_df.to_csv(final_csv_path, index=False, sep=",", encoding="utf-8")

    final_csv_path
else:
    final_csv_path = None

final_csv_path
