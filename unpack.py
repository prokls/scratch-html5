#!/usr/bin/env python

"""
    unpack.py
    =========

    Unpack scratch projects.

    (C) HTML5 dev team of Scratch & Catrobat
"""

import sys
import json
import os.path
import zipfile
import hashlib
import tempfile

CACHE_FOLDER = '.cache'
ERR_NOT_ZIP = -4


def hashfile(filepath):
    """Retrieve MD5 hash of a file"""
    md5_obj = hashlib.md5()
    with open(filepath, 'rb') as fp:
        buf = fp.read(2**24)
        while len(buf) > 0:
            md5_obj.update(buf)
            buf = fp.read(2**24)
    return md5_obj.hexdigest()


def get_project_id(jsonfile):
    """`jsonfile` is a project JSON file.
    Retrieve the JSON data and read project ID from it.
    """
    with open(jsonfile) as fp:
        data = json.load(fp)
        return int(data['info']['projectID'])


def unpack(zip_archive):
    """Unpack ZIP archive."""
    if not zipfile.is_zipfile(zip_archive):
        print("Not a ZIP archive: {}".format(zip_archive))
        return ERR_NOT_ZIP

    with zipfile.ZipFile(zip_archive) as zp:
        for zip_entry in zp.infolist():
            filename, fileext = os.path.splitext(zip_entry.filename)

            # 1. extract
            zp.extract(zip_entry)

            # 2. evaluate md5 hash
            md5sum = hashfile(zip_entry.filename)

            # 3. move file to filename with hash
            final_path = os.path.join(CACHE_FOLDER, md5sum + fileext)
            os.rename(zip_entry.filename, final_path)

            # 4. If project file, store file with project ID instead of MD5 hash
            if zip_entry.filename == 'project.json':
                proj_id = get_project_id(final_path)
                proj_filepath = os.path.join(CACHE_FOLDER, str(proj_id) + fileext)
                os.rename(final_path, proj_filepath)

    return 0


def main(zip_archives):
    """Main routine"""
    if not os.path.exists(CACHE_FOLDER):
        os.mkdir(CACHE_FOLDER)

    for zip_archive in zip_archives:
        res = unpack(zip_archive)
        if res != 0:
            return res
        print '[' + zip_archive.ljust(30) + '] extracted.'


if __name__ == '__main__':
    if len(sys.argv) == 1:
        print '  Extract scratch/catrobat project files into temporary repository'
        print '  in folder {} accessible via proxy.php from the web server' \
              .format(CACHE_FOLDER)
        print ''
        print 'Usage:'
        print '  ./unpack.py <.sb2 file to unpack #1> <.sb2 file to unpack #2> ...'
    else:
        sys.exit(main(sys.argv[1:]))